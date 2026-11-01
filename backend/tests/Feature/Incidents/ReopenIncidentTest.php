<?php

declare(strict_types=1);

// Reapertura por la API: pedirla con motivo, aprobarla o rechazarla quien supervisa, y reabrir directamente.
// Documentado en docs/backend/incidents.md

use App\Modules\Users\Infrastructure\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

// Incidente del solicitante 1, atendido por el agente 1 y cerrado por el supervisor
function closedIncident(): array
{
    actingAsDemo('solicitante1@trazo.test');
    $incident = test()->postJson('/api/v1/incidents', ['title' => 'Correo no sincroniza', 'description' => 'Desde el lunes.', 'priority' => 'high'])->json('data');

    actingAsDemo('supervisor@trazo.test');
    $agent = User::query()->where('email', 'agente1@trazo.test')->value('ulid');
    $version = test()->putJson("/api/v1/incidents/{$incident['id']}/assignee", ['assignee' => $agent, 'version' => 1])->json('data.version');
    foreach (['in_progress', 'resolved', 'closed'] as $status) {
        $version = test()->putJson("/api/v1/incidents/{$incident['id']}/status", ['status' => $status, 'version' => $version, 'note' => 'Listo'])->json('data.version');
    }

    return test()->getJson("/api/v1/incidents/{$incident['id']}")->json('data');
}

function reopenUrl(array $incident, string $action = ''): string
{
    return "/api/v1/incidents/{$incident['id']}/reopen{$action}";
}

it('el solicitante pide reabrir con un motivo y el supervisor lo aprueba', function (): void {
    $incident = closedIncident();

    actingAsDemo('solicitante1@trazo.test');
    $this->postJson(reopenUrl($incident, '-request'), ['reason' => ''])->assertUnprocessable()->assertJsonPath('errors.reason.0.code', 'REQUIRED');
    $requested = $this->postJson(reopenUrl($incident, '-request'), ['reason' => 'Volvió a fallar esta mañana'])
        ->assertCreated()
        ->assertJsonPath('data.reopen_request.reason', 'Volvió a fallar esta mañana')
        ->assertJsonPath('data.reopen_request.requested_by.name', 'Juan Pérez')
        ->assertJsonPath('data.actions.request_reopen', false)
        ->json('data');
    expect($requested['version'])->toBe($incident['version']);

    // Una sola solicitud pendiente por incidente
    $this->postJson(reopenUrl($incident, '-request'), ['reason' => 'Otra vez'])->assertConflict()->assertJsonPath('code', 'REOPEN_ALREADY_REQUESTED');

    actingAsDemo('supervisor@trazo.test');
    $this->getJson("/api/v1/incidents/{$incident['id']}")
        ->assertJsonPath('data.actions.decide_reopen', true)
        ->assertJsonPath('data.actions.reopen', false);
    $reopened = $this->postJson(reopenUrl($incident, '-request/approve'), ['version' => $incident['version']])
        ->assertOk()
        ->assertJsonPath('data.status.code', 'in_progress')
        ->assertJsonPath('data.resolved_at', null)
        ->assertJsonPath('data.reopen_request', null)
        ->json('data');
    expect(strtotime($reopened['due_date']))->toBeGreaterThan(time());

    $history = collect($this->getJson("/api/v1/incidents/{$incident['id']}/activities")->json('data'));
    expect($history->take(2)->pluck('type.code')->all())->toBe(['reopened', 'reopen_requested'])
        ->and($history->first()['note'])->toBe('Volvió a fallar esta mañana')
        ->and($history->first()['changes']['status']['from']['code'])->toBe('closed');
    expect(DB::table('incident_reopen_requests')->value('status'))->toBe('approved');
});

it('el supervisor rechaza con un motivo y el incidente sigue cerrado', function (): void {
    $incident = closedIncident();
    actingAsDemo('agente1@trazo.test');
    $this->postJson(reopenUrl($incident, '-request'), ['reason' => 'El cliente insiste'])->assertCreated();

    actingAsDemo('supervisor@trazo.test');
    $this->postJson(reopenUrl($incident, '-request/reject'), ['reason' => ''])->assertUnprocessable();
    $this->postJson(reopenUrl($incident, '-request/reject'), ['reason' => 'Es otro problema: abre un incidente nuevo'])
        ->assertOk()
        ->assertJsonPath('data.status.code', 'closed')
        ->assertJsonPath('data.reopen_request', null)
        ->assertJsonPath('data.actions.reopen', true);

    // Ya decidida: no se puede decidir de nuevo
    $this->postJson(reopenUrl($incident, '-request/approve'), ['version' => $incident['version']])
        ->assertConflict()->assertJsonPath('code', 'REOPEN_NOT_REQUESTED');
    expect(DB::table('incident_reopen_requests')->first())
        ->status->toBe('rejected')
        ->decision_note->toBe('Es otro problema: abre un incidente nuevo');
});

it('quien supervisa reabre directo con su motivo; sin asignado vuelve al estado inicial', function (): void {
    actingAsDemo('supervisor@trazo.test');
    $incident = $this->postJson('/api/v1/incidents', ['title' => 'Duplicado', 'description' => 'x', 'priority' => 'low'])->json('data');
    $cancelled = $this->putJson("/api/v1/incidents/{$incident['id']}/status", ['status' => 'cancelled', 'version' => 1, 'note' => 'Duplicado'])->json('data');

    $this->postJson(reopenUrl($cancelled), ['version' => $cancelled['version'], 'reason' => ''])->assertUnprocessable();
    $this->postJson(reopenUrl($cancelled), ['version' => $cancelled['version'], 'reason' => 'No era duplicado'])
        ->assertOk()
        ->assertJsonPath('data.status.code', 'open');
});

it('nadie decide su propia solicitud ni reabre sin permiso o lo que no ha terminado', function (): void {
    $incident = closedIncident();

    actingAsDemo('agente1@trazo.test');
    $this->postJson(reopenUrl($incident), ['version' => $incident['version'], 'reason' => 'x'])->assertForbidden();

    // Una solicitud del supervisor (con permiso de pedir) no la aprueba él mismo
    actingAsDemo('supervisor@trazo.test');
    DB::table('incident_reopen_requests')->insert([
        'incident_id' => DB::table('incidents')->where('ulid', $incident['id'])->value('id'),
        'requested_by' => auth()->id(), 'reason' => 'Mía', 'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
    ]);
    $this->postJson(reopenUrl($incident, '-request/approve'), ['version' => $incident['version']])
        ->assertForbidden()->assertJsonPath('code', 'REOPEN_OWN_REQUEST');
    $this->postJson(reopenUrl($incident), ['version' => $incident['version'], 'reason' => 'x'])
        ->assertConflict()->assertJsonPath('code', 'REOPEN_ALREADY_REQUESTED');

    // Lo que sigue abierto no se reabre
    actingAsDemo('solicitante2@trazo.test');
    $open = $this->postJson('/api/v1/incidents', ['title' => 'Abierto', 'description' => 'x', 'priority' => 'low'])->json('data');
    $this->postJson(reopenUrl($open, '-request'), ['reason' => 'x'])->assertConflict()->assertJsonPath('code', 'INCIDENT_NOT_FINALIZED');

    // Lo ajeno ni siquiera existe para quien no lo ve
    $this->postJson(reopenUrl($incident, '-request'), ['reason' => 'x'])->assertNotFound();
});

it('el listado filtra los que tienen una solicitud pendiente, con las mismas acciones del detalle', function (): void {
    $incident = closedIncident();
    closedIncident();
    actingAsDemo('solicitante1@trazo.test');
    $this->postJson(reopenUrl($incident, '-request'), ['reason' => 'Falla otra vez'])->assertCreated();

    actingAsDemo('supervisor@trazo.test');
    $this->getJson('/api/v1/incidents?filter[reopen_requested]=1')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $incident['id'])
        ->assertJsonPath('data.0.reopen_requested', true)
        ->assertJsonPath('data.0.actions.decide_reopen', true)
        ->assertJsonPath('data.0.actions.reopen', false);
});
