<?php

declare(strict_types=1);

// Centro de notificaciones por la API: quién recibe cada aviso, los mensajes agrupados por incidente, marcar como
// leídas, los incidentes que ya no se ven, la limpieza y el aviso de tiempo real. Documentado en docs/backend/notifications.md

use App\Modules\Notifications\Infrastructure\DatabaseIncidentParticipants;
use App\Modules\Realtime\Infrastructure\Events\NotificationsSignal;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

// Incidente del solicitante 1, sin asignar
function notifiedIncident(): array
{
    actingAsDemo('solicitante1@trazo.test');

    return test()->postJson('/api/v1/incidents', ['title' => 'La impresora no imprime', 'description' => 'Sale en blanco.', 'priority' => 'low'])->json('data');
}

function notificationsOf(string $email, string $category): array
{
    actingAsDemo($email);

    return test()->getJson("/api/v1/notifications?category={$category}")->assertOk()->json();
}

it('avisa los mensajes a quienes participan y los agrupa por incidente', function (): void {
    $incident = notifiedIncident();
    $url = "/api/v1/incidents/{$incident['id']}/comments";

    actingAsDemo('supervisor@trazo.test');
    $this->postJson($url, ['body' => '¿Desde qué equipo?'])->assertCreated();
    $this->postJson($url, ['body' => 'Y ¿desde cuándo?'])->assertCreated();
    actingAsDemo('agente1@trazo.test');
    $this->postJson($url, ['body' => 'Yo lo reviso.'])->assertCreated();

    // El solicitante (creador) tiene un solo aviso con los tres mensajes, el último primero
    $mine = notificationsOf('solicitante1@trazo.test', 'conversations');
    expect($mine['data'])->toHaveCount(1)
        ->and($mine['data'][0])->toMatchArray([
            'type' => 'comment_received',
            'occurrences' => 3,
            'actor' => ['name' => 'Laura Gómez'],
            'excerpt' => 'Yo lo reviso.',
            'read' => false,
        ])
        ->and($mine['data'][0]['incident']['code'])->toBe($incident['code'])
        ->and($mine['meta']['unread'])->toBe(['conversations' => 1, 'activity' => 0]);

    // El supervisor ya escribió: recibe el del agente; el agente no recibe su propio mensaje
    expect(notificationsOf('supervisor@trazo.test', 'conversations')['data'][0]['occurrences'])->toBe(1)
        ->and(notificationsOf('agente1@trazo.test', 'conversations')['data'])->toBe([]);
});

it('avisa la asignación y los cambios de estado, nunca a quien los hizo', function (): void {
    $incident = notifiedIncident();
    $version = $incident['version'];

    actingAsDemo('supervisor@trazo.test');
    $version = $this->putJson("/api/v1/incidents/{$incident['id']}/assignee", ['assignee' => demoUlid('agente1@trazo.test'), 'version' => $version])->json('data.version');
    actingAsDemo('agente1@trazo.test');
    $this->putJson("/api/v1/incidents/{$incident['id']}/status", ['status' => 'in_progress', 'version' => $version])->assertOk();

    $agent = notificationsOf('agente1@trazo.test', 'activity')['data'];
    expect(array_column($agent, 'type'))->toBe(['incident_assigned'])
        ->and($agent[0]['actor']['name'])->toBe('Carlos Mejía');

    $requester = notificationsOf('solicitante1@trazo.test', 'activity')['data'];
    expect(array_column($requester, 'type'))->toBe(['status_changed'])
        ->and($requester[0]['status'])->toBe('En progreso');

    // El supervisor asignó: de eso no recibe nada (solo el aviso de coordinación por el incidente sin asignar)
    expect(array_column(notificationsOf('supervisor@trazo.test', 'activity')['data'], 'type'))->toBe(['unassigned_created']);
});

it('tomar un incidente uno mismo no se avisa', function (): void {
    $incident = notifiedIncident();
    actingAsDemo('agente1@trazo.test');
    $this->putJson("/api/v1/incidents/{$incident['id']}/assignee", ['assignee' => demoUlid('agente1@trazo.test'), 'version' => $incident['version']])->assertOk();

    expect(notificationsOf('agente1@trazo.test', 'activity')['data'])->toBe([]);
});

it('el asignado de "creado" es el de ese momento, aunque la cola corra después de otro cambio', function (): void {
    $incident = notifiedIncident();
    actingAsDemo('agente1@trazo.test');
    $this->putJson("/api/v1/incidents/{$incident['id']}/assignee", ['assignee' => demoUlid('agente1@trazo.test'), 'version' => $incident['version']])->assertOk();

    $created = (int) DB::table('incident_activities as a')->join('activity_types as t', 't.id', '=', 'a.activity_type_id')
        ->join('incidents as i', 'i.id', '=', 'a.incident_id')
        ->where('i.ulid', $incident['id'])->where('t.code', 'created')->value('a.id');

    expect(app(DatabaseIncidentParticipants::class)->ofActivity($created)?->assignee)->toBeNull();
});

it('avisa a quien pidió la reapertura que se la rechazaron, con el motivo', function (): void {
    $incident = notifiedIncident();
    actingAsDemo('supervisor@trazo.test');
    $this->putJson("/api/v1/incidents/{$incident['id']}/status", ['status' => 'cancelled', 'version' => $incident['version'], 'note' => 'Duplicado'])->assertOk();
    actingAsDemo('solicitante1@trazo.test');
    $this->postJson("/api/v1/incidents/{$incident['id']}/reopen-request", ['reason' => 'No era duplicado'])->assertCreated();
    actingAsDemo('supervisor@trazo.test');
    $this->postJson("/api/v1/incidents/{$incident['id']}/reopen-request/reject", ['reason' => 'Sí lo era: ver TZ-26-0001'])->assertOk();

    $activity = notificationsOf('solicitante1@trazo.test', 'activity')['data'];
    expect(array_column($activity, 'type'))->toBe(['reopen_rejected', 'status_changed'])
        ->and($activity[0]['excerpt'])->toBe('Sí lo era: ver TZ-26-0001');
});

it('marca como leídas una, una pestaña o las de un incidente, y solo las propias', function (): void {
    $incident = notifiedIncident();
    actingAsDemo('supervisor@trazo.test');
    $this->postJson("/api/v1/incidents/{$incident['id']}/comments", ['body' => 'Hola'])->assertCreated();
    $this->putJson("/api/v1/incidents/{$incident['id']}/status", ['status' => 'in_progress', 'version' => $incident['version']])->assertOk();

    $id = notificationsOf('solicitante1@trazo.test', 'conversations')['data'][0]['id'];
    $this->getJson('/api/v1/notifications/unread')->assertOk()->assertJsonPath('data', ['conversations' => 1, 'activity' => 1]);

    // Una ajena no existe para quien pregunta
    actingAsDemo('agente1@trazo.test');
    $this->postJson("/api/v1/notifications/{$id}/read")->assertNotFound()->assertJsonPath('code', 'NOTIFICATION_NOT_FOUND');

    actingAsDemo('solicitante1@trazo.test');
    $this->postJson("/api/v1/notifications/{$id}/read")->assertOk()->assertJsonPath('data', ['conversations' => 0, 'activity' => 1]);
    $this->postJson('/api/v1/notifications/read', ['category' => 'activity', 'incident' => $incident['id']])
        ->assertOk()->assertJsonPath('data', ['conversations' => 0, 'activity' => 0]);
    $this->postJson('/api/v1/notifications/read', ['category' => 'todas'])
        ->assertUnprocessable()->assertJsonPath('errors.category.0.code', 'INVALID_OPTION');

    // Leído el aviso, el siguiente mensaje abre uno nuevo
    actingAsDemo('supervisor@trazo.test');
    $this->postJson("/api/v1/incidents/{$incident['id']}/comments", ['body' => '¿Sigue igual?'])->assertCreated();
    expect(array_column(notificationsOf('solicitante1@trazo.test', 'conversations')['data'], 'read'))->toBe([false, true]);
});

it('no muestra el incidente ni lo que se dijo si la persona ya no puede verlo', function (): void {
    $incident = notifiedIncident();
    actingAsDemo('supervisor@trazo.test');
    $this->postJson("/api/v1/incidents/{$incident['id']}/comments", ['body' => 'Dato reservado'])->assertCreated();
    $this->deleteJson("/api/v1/incidents/{$incident['id']}")->assertNoContent();

    $item = notificationsOf('solicitante1@trazo.test', 'conversations')['data'][0];
    expect($item['incident'])->toBeNull()->and($item['excerpt'])->toBeNull();
});

it('pagina de a 20 y borra las de más de 90 días', function (): void {
    $incident = notifiedIncident();
    actingAsDemo('supervisor@trazo.test');
    $version = $incident['version'];
    // Ida y vuelta entre En progreso y En espera: cada cambio es un aviso para el solicitante
    foreach (range(1, 11) as $round) {
        $version = $this->putJson("/api/v1/incidents/{$incident['id']}/status", ['status' => 'in_progress', 'version' => $version])->json('data.version');
        $version = $this->putJson("/api/v1/incidents/{$incident['id']}/status", ['status' => 'on_hold', 'version' => $version])->json('data.version');
    }

    $first = notificationsOf('solicitante1@trazo.test', 'activity');
    expect($first['data'])->toHaveCount(20)->and($first['meta']['before'])->toBe($first['data'][19]['id']);
    $rest = $this->getJson("/api/v1/notifications?category=activity&before={$first['meta']['before']}")->json();
    expect($rest['data'])->toHaveCount(2)->and($rest['meta']['before'])->toBeNull();

    // 22 del solicitante + "nuevo sin asignar" para el supervisor y el Super admin
    DB::table('notifications')->limit(5)->update(['created_at' => now()->subDays(91)]);
    $this->artisan('notifications:prune')->assertSuccessful();
    expect(DB::table('notifications')->count())->toBe(19);
});

it('avisa en tiempo real por el canal personal de cada destinatario', function (): void {
    Event::fake([NotificationsSignal::class]);
    $incident = notifiedIncident();
    actingAsDemo('supervisor@trazo.test');
    $this->postJson("/api/v1/incidents/{$incident['id']}/comments", ['body' => 'Hola'])->assertCreated();

    $channel = 'private-users.'.demoUlid('solicitante1@trazo.test');
    Event::assertDispatched(NotificationsSignal::class, fn (NotificationsSignal $signal): bool => array_map(
        fn (PrivateChannel $c): string => $c->name,
        $signal->broadcastOn(),
    ) === [$channel]);
});
