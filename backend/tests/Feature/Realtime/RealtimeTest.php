<?php

declare(strict_types=1);

// Tiempo real por la API: autorización de cada canal según el rol, avisos de incidentes a los canales correctos (sin
// datos y sin avisarse a uno mismo de tomarlo), aviso de asignación y avisos de sesión. Documentado en docs/backend/realtime.md

use App\Modules\Realtime\Infrastructure\Events\AssignedSignal;
use App\Modules\Realtime\Infrastructure\Events\IncidentSignal;
use App\Modules\Realtime\Infrastructure\Events\SessionSignal;
use App\Modules\Realtime\Infrastructure\Events\Signal;
use App\Modules\Realtime\Infrastructure\RealtimeServiceProvider;
use App\Modules\Users\Infrastructure\Models\User;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    cache()->flush();
});

function realtimeAs(string $email): User
{
    app('auth')->forgetGuards();

    return actingAsDemo($email);
}

/**
 * @return list<string>
 */
function channelsOf(Signal $signal): array
{
    return array_map(fn (PrivateChannel $channel): string => $channel->name, $signal->broadcastOn());
}

// Reverb firma localmente: no hace falta el servidor para probar quién puede suscribirse
function useSigningBroadcaster(): void
{
    config([
        'broadcasting.default' => 'reverb',
        'broadcasting.connections.reverb' => [
            'driver' => 'reverb', 'key' => 'clave-prueba', 'secret' => 'secreto-prueba', 'app_id' => 'trazo',
            'options' => ['host' => 'localhost', 'port' => 8080, 'scheme' => 'http', 'useTLS' => false],
        ],
    ]);
    app(BroadcastManager::class)->purge();
    (new RealtimeServiceProvider(app()))->boot();
}

describe('suscripción a canales', function (): void {
    beforeEach(fn () => useSigningBroadcaster());

    it('cada rol recibe firma solo de los canales que su visibilidad permite, todos en una petición', function (string $email, bool $all, bool $unassigned): void {
        realtimeAs($email);
        $own = 'private-users.'.demoUlid($email);
        $other = 'private-users.'.demoUlid('superadmin@trazo.test');

        $signatures = $this->postJson('/api/v1/broadcasting/auth', [
            'socket_id' => '123.456',
            'channels' => ['private-incidents.all', 'private-incidents.unassigned', $own, $other],
        ])->assertOk()->json('data');

        expect($signatures['private-incidents.all'] !== null)->toBe($all)
            ->and($signatures['private-incidents.unassigned'] !== null)->toBe($unassigned)
            ->and($signatures[$own])->toHaveKey('auth')
            ->and($signatures[$other])->toBeNull();
    })->with([
        'solicitante' => ['solicitante1@trazo.test', false, false],
        'agente' => ['agente1@trazo.test', false, true],
        'supervisor' => ['supervisor@trazo.test', true, true],
        'administrador' => ['admin@trazo.test', false, false],
    ]);

    it('sin sesión, o con un ingreso a medias (falta el MFA), no se firma ninguna suscripción', function (): void {
        $body = ['socket_id' => '123.456', 'channels' => ['private-incidents.all']];
        $this->postJson('/api/v1/broadcasting/auth', $body)->assertUnauthorized();

        $halfway = User::query()->where('email', 'supervisor@trazo.test')->firstOrFail()->createToken('ingreso', ['mfa'])->plainTextToken;
        $this->withToken($halfway)->postJson('/api/v1/broadcasting/auth', $body)->assertUnauthorized();
    });
});

it('rechaza un id de conexión inventado y más de 10 canales', function (): void {
    realtimeAs('agente1@trazo.test');

    $this->postJson('/api/v1/broadcasting/auth', ['socket_id' => 'abc', 'channels' => ['private-incidents.all']])
        ->assertUnprocessable();
    $this->postJson('/api/v1/broadcasting/auth', ['socket_id' => '1.2', 'channels' => array_fill(0, 11, 'private-incidents.all')])
        ->assertUnprocessable();
});

it('entrega la clave pública y la ruta de conexión, nunca el secreto', function (): void {
    realtimeAs('agente1@trazo.test');

    $response = $this->getJson('/api/v1/realtime')->assertOk()->assertJsonPath('data.path', '/ws');
    expect($response->json('data'))->toHaveKeys(['key', 'path'])->not->toHaveKey('secret');
});

describe('avisos de incidentes', function (): void {
    beforeEach(fn () => Event::fake([IncidentSignal::class, AssignedSignal::class, SessionSignal::class]));

    it('crear sin asignar avisa al general, al de sin asignar y a quien lo creó, solo con el id', function (): void {
        realtimeAs('supervisor@trazo.test');
        $id = $this->postJson('/api/v1/incidents', ['title' => 'Impresora', 'description' => 'No imprime', 'priority' => 'low'])
            ->assertCreated()->json('data.id');

        Event::assertDispatched(IncidentSignal::class, function (IncidentSignal $signal) use ($id): bool {
            return $signal->broadcastWith() === ['id' => $id, 'change' => 'created']
                && channelsOf($signal) === ['private-incidents.all', 'private-incidents.unassigned', 'private-users.'.demoUlid('supervisor@trazo.test')];
        });
        Event::assertNotDispatched(AssignedSignal::class);
    });

    it('asignar avisa también a quien lo recibe, y le llega el aviso personal de asignación', function (): void {
        realtimeAs('supervisor@trazo.test');
        $incident = $this->postJson('/api/v1/incidents', ['title' => 'Sin red en el piso 3', 'description' => 'Sin red', 'priority' => 'low'])->json('data');
        $agent = demoUlid('agente1@trazo.test');

        $this->putJson("/api/v1/incidents/{$incident['id']}/assignee", ['assignee' => $agent, 'version' => $incident['version']])->assertOk();

        Event::assertDispatched(IncidentSignal::class, fn (IncidentSignal $signal): bool => $signal->change->value === 'updated'
            && in_array('private-incidents.unassigned', channelsOf($signal), true)
            && in_array("private-users.{$agent}", channelsOf($signal), true));
        Event::assertDispatched(AssignedSignal::class, fn (AssignedSignal $signal): bool => channelsOf($signal) === ["private-users.{$agent}"]
            && $signal->broadcastWith() === ['id' => $incident['id']]);
    });

    it('tomar un incidente uno mismo no genera el aviso de asignación', function (): void {
        realtimeAs('supervisor@trazo.test');
        $incident = $this->postJson('/api/v1/incidents', ['title' => 'Correo', 'description' => 'No llega', 'priority' => 'low'])->json('data');

        realtimeAs('agente1@trazo.test');
        $this->putJson("/api/v1/incidents/{$incident['id']}/assignee", ['assignee' => demoUlid('agente1@trazo.test'), 'version' => $incident['version']])
            ->assertOk();

        Event::assertNotDispatched(AssignedSignal::class);
    });
});

describe('avisos de sesión', function (): void {
    beforeEach(fn () => Event::fake([IncidentSignal::class, AssignedSignal::class, SessionSignal::class]));

    it('cambiar el rol o desactivar a alguien le avisa por su canal personal', function (): void {
        realtimeAs('admin@trazo.test');
        $agent = demoUlid('agente2@trazo.test');

        $this->putJson("/api/v1/users/{$agent}/roles", ['role' => 'requester'])->assertOk();
        $this->postJson("/api/v1/users/{$agent}/deactivate")->assertOk();

        Event::assertDispatchedTimes(SessionSignal::class, 2);
        Event::assertDispatched(SessionSignal::class, fn (SessionSignal $signal): bool => channelsOf($signal) === ["private-users.{$agent}"]
            && $signal->broadcastWith() === []);
    });

    it('editar un rol avisa a todas las personas que lo tienen', function (): void {
        realtimeAs('superadmin@trazo.test');
        $role = $this->getJson('/api/v1/roles/agent')->json('data');

        $this->patchJson('/api/v1/roles/agent', [
            'name' => 'Agente', 'level' => 40, 'permissions' => array_values(array_diff($role['permissions'], ['incidents.take'])),
            'mfa_required' => true, 'grace_logins' => 1,
        ])->assertOk();

        $agents = User::query()->whereIn('email', ['agente1@trazo.test', 'agente2@trazo.test', 'agente3@trazo.test'])->pluck('ulid')
            ->map(fn (string $ulid): string => "private-users.{$ulid}")->all();
        Event::assertDispatched(SessionSignal::class, fn (SessionSignal $signal): bool => array_diff($agents, channelsOf($signal)) === []);
    });
});
