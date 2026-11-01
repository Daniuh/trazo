<?php

declare(strict_types=1);

// Creación de incidentes: datos, vencimiento calculado, asignación inicial, idempotencia e historial.
// Documentado en docs/backend/incidents.md

use App\Modules\Users\Infrastructure\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->freezeTime());

const NEW_INCIDENT = ['title' => 'No carga facturación', 'description' => 'Pantalla en blanco al abrir facturas.', 'priority' => 'critical'];

it('crea el incidente abierto, con código legible y vencimiento según la prioridad', function (): void {
    actingAsDemo('solicitante1@trazo.test');

    $response = $this->postJson('/api/v1/incidents', NEW_INCIDENT);

    $response->assertCreated()
        ->assertJsonPath('data.status.code', 'open')
        ->assertJsonPath('data.priority.code', 'critical')
        ->assertJsonPath('data.creator.name', 'Juan Pérez')
        ->assertJsonPath('data.version', 1)
        ->assertJsonPath('data.actions.update', true)
        ->assertJsonPath('data.due_date', now()->addHours(4)->toAtomString());
    expect($response->json('data.code'))->toMatch('/^TZ-\d{2}-\d{4}$/');
    expect(DB::table('incident_activities')->count())->toBe(1);
});

it('respeta el vencimiento indicado y rechaza uno en el pasado', function (): void {
    actingAsDemo('solicitante1@trazo.test');
    $due = now()->addDays(10)->startOfMinute();

    $this->postJson('/api/v1/incidents', [...NEW_INCIDENT, 'due_date' => $due->toAtomString()])
        ->assertJsonPath('data.due_date', $due->toAtomString());

    $this->postJson('/api/v1/incidents', [...NEW_INCIDENT, 'due_date' => now()->subDay()->toAtomString()])
        ->assertUnprocessable()
        ->assertJsonPath('errors.due_date.0.code', 'DATE_TOO_EARLY');
});

it('solo quien puede asignar asigna al crear, y solo a quien atiende incidentes', function (): void {
    $agent = User::query()->where('email', 'agente1@trazo.test')->firstOrFail();
    $requester = User::query()->where('email', 'solicitante2@trazo.test')->firstOrFail();

    actingAsDemo('solicitante1@trazo.test');
    $this->postJson('/api/v1/incidents', [...NEW_INCIDENT, 'assignee' => $agent->ulid])->assertForbidden();

    actingAsDemo('supervisor@trazo.test');
    $this->postJson('/api/v1/incidents', [...NEW_INCIDENT, 'assignee' => $agent->ulid])
        ->assertCreated()
        ->assertJsonPath('data.assignee.name', 'Laura Gómez');
    $this->postJson('/api/v1/incidents', [...NEW_INCIDENT, 'assignee' => $requester->ulid])
        ->assertUnprocessable()
        ->assertJsonPath('code', 'INVALID_ASSIGNEE');
});

it('quien puede tomar incidentes se los asigna al crear, pero no a otra persona', function (): void {
    $agent = User::query()->where('email', 'agente1@trazo.test')->firstOrFail();
    $other = User::query()->where('email', 'agente2@trazo.test')->firstOrFail();
    $requester = User::query()->where('email', 'solicitante2@trazo.test')->firstOrFail();

    actingAsDemo('agente1@trazo.test');
    $created = $this->postJson('/api/v1/incidents', [...NEW_INCIDENT, 'assignee' => $agent->ulid])
        ->assertCreated()
        ->assertJsonPath('data.assignee.name', 'Laura Gómez');
    $this->postJson('/api/v1/incidents', [...NEW_INCIDENT, 'assignee' => $other->ulid])->assertForbidden();
    $this->postJson('/api/v1/incidents', [...NEW_INCIDENT, 'assignee' => $requester->ulid])->assertForbidden();

    $activity = DB::table('incident_activities')->where('incident_id', DB::table('incidents')->where('ulid', $created->json('data.id'))->value('id'))->first();
    expect($activity->activity_type_id)->toBe(DB::table('activity_types')->where('code', 'created')->value('id'))
        ->and(json_decode($activity->changes, true)['assignee']['to']['id'])->toBe($agent->ulid);
});

it('valida forma y referencias', function (): void {
    actingAsDemo('solicitante1@trazo.test');

    $this->postJson('/api/v1/incidents', ['title' => str_repeat('a', 81), 'priority' => 'urgente'])
        ->assertUnprocessable()
        ->assertJsonPath('errors.title.0.code', 'TOO_LONG')
        ->assertJsonPath('errors.title.0.params.max', '80')
        ->assertJsonPath('errors.description.0.code', 'REQUIRED')
        ->assertJsonPath('errors.priority.0.code', 'INVALID_REFERENCE');
});

it('el título va de 5 a 80 caracteres, sin contar los espacios de los extremos', function (): void {
    actingAsDemo('solicitante1@trazo.test');

    $this->postJson('/api/v1/incidents', [...NEW_INCIDENT, 'title' => '  Red  '])
        ->assertUnprocessable()
        ->assertJsonPath('errors.title.0.code', 'TOO_SHORT')
        ->assertJsonPath('errors.title.0.params.min', '5');
    $this->postJson('/api/v1/incidents', [...NEW_INCIDENT, 'title' => str_repeat('a', 80)])->assertCreated();
});

it('con la misma Idempotency-Key no crea dos veces', function (): void {
    actingAsDemo('solicitante1@trazo.test');
    $headers = ['Idempotency-Key' => 'clave-de-prueba-1'];

    $first = $this->postJson('/api/v1/incidents', NEW_INCIDENT, $headers)->assertCreated();
    $this->postJson('/api/v1/incidents', NEW_INCIDENT, $headers)
        ->assertCreated()
        ->assertHeader('Idempotent-Replayed', 'true')
        ->assertJsonPath('data.id', $first->json('data.id'));
    $this->postJson('/api/v1/incidents', [...NEW_INCIDENT, 'title' => 'Otro título'], $headers)
        ->assertConflict()
        ->assertJsonPath('code', 'IDEMPOTENCY_KEY_REUSED');

    expect(DB::table('incidents')->count())->toBe(1);
});

it('exige sesión', function (): void {
    $this->postJson('/api/v1/incidents', NEW_INCIDENT)->assertUnauthorized();
});
