<?php

declare(strict_types=1);

// Conversación de un incidente por la API: quién lee y escribe, borrar el propio durante 5 minutos, páginas y el aviso
// de tiempo real. Documentado en docs/backend/incidents.md

use App\Modules\Realtime\Infrastructure\Events\CommentSignal;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

// Incidente del solicitante 1, sin asignar
function conversationIncident(): string
{
    actingAsDemo('solicitante1@trazo.test');

    return (string) test()->postJson('/api/v1/incidents', ['title' => 'Sin red en el piso 3', 'description' => 'Desde hoy.', 'priority' => 'low'])->json('data.id');
}

function commentsUrl(string $incident): string
{
    return "/api/v1/incidents/{$incident}/comments";
}

it('quien ve el incidente lee y escribe; cada mensaje dice si es propio', function (): void {
    $incident = conversationIncident();
    $this->postJson(commentsUrl($incident), ['body' => '  ¿Alguien lo revisa hoy?  '])
        ->assertCreated()
        ->assertJsonPath('data.body', '¿Alguien lo revisa hoy?')
        ->assertJsonPath('data.author.name', 'Juan Pérez')
        ->assertJsonPath('data.mine', true)
        ->assertJsonPath('data.actions.delete', true);

    actingAsDemo('supervisor@trazo.test');
    $this->postJson(commentsUrl($incident), ['body' => 'Sí, en la tarde.'])->assertCreated();
    $this->getJson(commentsUrl($incident))
        ->assertOk()
        ->assertJsonPath('meta.total', 2)
        ->assertJsonPath('meta.before', null)
        ->assertJsonPath('data.0.body', 'Sí, en la tarde.')
        ->assertJsonPath('data.0.mine', true)
        ->assertJsonPath('data.1.mine', false)
        ->assertJsonPath('data.1.actions.delete', false);
    $this->getJson("/api/v1/incidents/{$incident}")->assertJsonPath('data.comments_count', 2)->assertJsonPath('data.actions.comment', true);
});

it('quien no ve el incidente no lee ni escribe, y el mensaje se valida', function (): void {
    $incident = conversationIncident();
    $this->postJson(commentsUrl($incident), ['body' => '   '])->assertUnprocessable()->assertJsonPath('errors.body.0.code', 'REQUIRED');
    $this->postJson(commentsUrl($incident), ['body' => str_repeat('a', 2001)])->assertUnprocessable()->assertJsonPath('errors.body.0.code', 'TOO_LONG');

    // El solicitante 2 no ve los incidentes del solicitante 1
    actingAsDemo('solicitante2@trazo.test');
    $this->getJson(commentsUrl($incident))->assertNotFound();
    $this->postJson(commentsUrl($incident), ['body' => 'Hola'])->assertNotFound();
});

it('se escribe también en un incidente terminado, sin cambiar su estado', function (): void {
    $incident = conversationIncident();
    actingAsDemo('supervisor@trazo.test');
    $this->putJson("/api/v1/incidents/{$incident}/status", ['status' => 'cancelled', 'version' => 1, 'note' => 'Duplicado'])->assertOk();

    actingAsDemo('solicitante1@trazo.test');
    $this->postJson(commentsUrl($incident), ['body' => '¿Cuál era el original?'])->assertCreated();
    $this->getJson("/api/v1/incidents/{$incident}")->assertJsonPath('data.status.code', 'cancelled');
});

it('solo quien escribió borra su mensaje, una vez y durante 5 minutos', function (): void {
    $this->freezeTime();
    $incident = conversationIncident();
    $first = (string) $this->postJson(commentsUrl($incident), ['body' => 'Me equivoqué de incidente'])->json('data.id');
    $second = (string) $this->postJson(commentsUrl($incident), ['body' => 'Este sí va'])->json('data.id');

    actingAsDemo('supervisor@trazo.test');
    $this->deleteJson(commentsUrl($incident)."/{$first}")->assertForbidden()->assertJsonPath('code', 'COMMENT_NOT_OWN');

    actingAsDemo('solicitante1@trazo.test');
    $this->deleteJson(commentsUrl($incident)."/{$first}")
        ->assertOk()
        ->assertJsonPath('data.body', null)
        ->assertJsonPath('data.actions.delete', false);
    $this->deleteJson(commentsUrl($incident)."/{$first}")->assertConflict()->assertJsonPath('code', 'COMMENT_ALREADY_DELETED');

    $this->travel(6)->minutes();
    $this->deleteJson(commentsUrl($incident)."/{$second}")->assertConflict()->assertJsonPath('code', 'COMMENT_DELETE_EXPIRED');
    // El borrado queda en su lugar ("Mensaje eliminado"), pero ya no cuenta
    $this->getJson(commentsUrl($incident))->assertJsonCount(2, 'data')->assertJsonPath('meta.total', 1);
});

it('se lee de a 50, del más nuevo al más viejo', function (): void {
    $incident = conversationIncident();
    $incidentId = DB::table('incidents')->where('ulid', $incident)->value('id');
    $authorId = DB::table('users')->where('email', 'solicitante1@trazo.test')->value('id');
    DB::table('incident_comments')->insert(array_map(fn (int $n): array => [
        'ulid' => strtolower((string) Str::ulid()),
        'incident_id' => $incidentId,
        'user_id' => $authorId,
        'body' => "Mensaje {$n}",
        'created_at' => now(),
    ], range(1, 55)));

    $first = $this->getJson(commentsUrl($incident))->assertJsonCount(50, 'data')->assertJsonPath('data.0.body', 'Mensaje 55');
    $this->getJson(commentsUrl($incident).'?before='.$first->json('meta.before'))
        ->assertJsonCount(5, 'data')
        ->assertJsonPath('data.4.body', 'Mensaje 1')
        ->assertJsonPath('meta.before', null);
});

it('avisa por tiempo real a los mismos canales que un cambio del incidente', function (): void {
    $incident = conversationIncident();
    Event::fake([CommentSignal::class]);

    $this->postJson(commentsUrl($incident), ['body' => 'Hola'])->assertCreated();

    $creator = DB::table('users')->where('email', 'solicitante1@trazo.test')->value('ulid');
    Event::assertDispatched(CommentSignal::class, function (CommentSignal $signal) use ($incident, $creator): bool {
        $channels = array_map(fn (PrivateChannel $channel): string => $channel->name, $signal->broadcastOn());

        return $signal->broadcastWith() === ['id' => $incident, 'change' => 'added']
            && in_array('private-incidents.all', $channels, true)
            && in_array('private-incidents.unassigned', $channels, true)
            && in_array("private-users.{$creator}", $channels, true);
    });
});
