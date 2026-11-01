<?php

declare(strict_types=1);

// Ciclo de vida por la API: ver, editar, cambiar estado, asignar, eliminar y restaurar, con sus reglas.
// Documentado en docs/backend/incidents.md

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

// Un incidente nuevo creado por el solicitante 1
function reported(): array
{
    actingAsDemo('solicitante1@trazo.test');

    return test()->postJson('/api/v1/incidents', ['title' => 'Impresora sin conexión', 'description' => 'No imprime desde ayer.', 'priority' => 'medium'])->json('data');
}

function moveTo(array $incident, string $status, ?string $note = null): TestResponse
{
    // Resuelto y Cancelado piden nota; si la prueba no la da, va una de ejemplo
    $note ??= in_array($status, ['resolved', 'cancelled'], true) ? 'Nota de la prueba' : null;

    return test()->putJson("/api/v1/incidents/{$incident['id']}/status", ['status' => $status, 'version' => $incident['version'], 'note' => $note]);
}

it('cada quien ve solo lo que le corresponde, y lo ajeno responde 404', function (): void {
    $incident = reported();

    actingAsDemo('solicitante2@trazo.test');
    $this->getJson("/api/v1/incidents/{$incident['id']}")->assertNotFound();

    actingAsDemo('agente1@trazo.test');
    $this->getJson("/api/v1/incidents/{$incident['id']}")->assertOk()->assertJsonPath('data.actions.take', true);
});

it('edita solo lo que cambió, sube la versión y lo deja en el historial', function (): void {
    $incident = reported();

    $this->putJson("/api/v1/incidents/{$incident['id']}", [
        'title' => 'Impresora del piso 3 sin conexión', 'description' => $incident['description'], 'priority' => 'high', 'version' => 1,
    ])->assertOk()->assertJsonPath('data.version', 2)->assertJsonPath('data.priority.code', 'high');

    $changes = json_decode((string) DB::table('incident_activities')->latest('id')->value('changes'), true);
    expect(array_keys($changes))->toBe(['title', 'priority'])
        ->and($changes['priority']['from']['code'])->toBe('medium');
});

it('rechaza guardar sobre una versión vieja', function (): void {
    $incident = reported();
    $edit = ['title' => 'Nuevo título', 'description' => 'x', 'priority' => 'low', 'version' => 1];

    $this->putJson("/api/v1/incidents/{$incident['id']}", $edit)->assertOk();
    $this->putJson("/api/v1/incidents/{$incident['id']}", [...$edit, 'title' => 'Otro título'])
        ->assertConflict()
        ->assertJsonPath('code', 'VERSION_CONFLICT');
});

it('el agente toma el incidente y lo trabaja; el solicitante ya no puede editarlo', function (): void {
    $incident = reported();

    actingAsDemo('agente1@trazo.test');
    $taken = $this->putJson("/api/v1/incidents/{$incident['id']}/assignee", ['assignee' => demoUlid('agente1@trazo.test'), 'version' => 1])
        ->assertOk()->assertJsonPath('data.assignee.name', 'Laura Gómez')->json('data');
    $working = moveTo($taken, 'in_progress')->assertOk()->json('data');
    $resolved = moveTo($working, 'resolved')->assertOk()->json('data');
    expect($resolved['resolved_at'])->not->toBeNull()
        ->and(array_column($resolved['next_statuses'], 'code'))->toBe(['in_progress', 'closed']);

    actingAsDemo('solicitante1@trazo.test');
    $this->putJson("/api/v1/incidents/{$incident['id']}", ['title' => 'Título nuevo', 'description' => 'y', 'priority' => 'low', 'version' => $resolved['version']])
        ->assertConflict()
        ->assertJsonPath('code', 'INCIDENT_EDIT_LOCKED');
});

it('exige la nota al resolver o cancelar y la guarda en el historial', function (): void {
    $incident = reported();
    actingAsDemo('supervisor@trazo.test');

    $this->putJson("/api/v1/incidents/{$incident['id']}/status", ['status' => 'cancelled', 'version' => 1, 'note' => '   '])
        ->assertUnprocessable()
        ->assertJsonPath('code', 'STATUS_NOTE_REQUIRED')
        ->assertJsonPath('params.status', 'cancelled');
    $this->putJson("/api/v1/incidents/{$incident['id']}/status", ['status' => 'cancelled', 'version' => 1, 'note' => str_repeat('a', 1001)])
        ->assertUnprocessable()
        ->assertJsonPath('errors.note.0.code', 'TOO_LONG');

    // En los demás cambios la nota es opcional
    $working = moveTo($incident, 'in_progress', 'Empiezo por revisar el cable de red')->assertOk()->json('data');
    moveTo($working, 'cancelled', 'Se reportó dos veces')->assertOk();

    $notes = collect($this->getJson("/api/v1/incidents/{$incident['id']}/activities")->json('data'))->pluck('note')->all();
    expect($notes)->toBe(['Se reportó dos veces', 'Empiezo por revisar el cable de red', null]);
});

it('solo permite las transiciones del catálogo', function (): void {
    $incident = reported();
    actingAsDemo('supervisor@trazo.test');

    moveTo($incident, 'closed')
        ->assertConflict()
        ->assertJsonPath('code', 'INVALID_STATUS_TRANSITION')
        ->assertJsonPath('params.from', 'open');
});

it('un incidente cerrado ya no se modifica', function (): void {
    $incident = reported();
    actingAsDemo('supervisor@trazo.test');

    $cancelled = moveTo($incident, 'cancelled')->assertOk()->json('data');
    expect($cancelled['actions'])->toMatchArray(['update' => false, 'change_status' => false, 'assign' => false]);

    $this->putJson("/api/v1/incidents/{$incident['id']}/assignee", ['assignee' => null, 'version' => $cancelled['version']])
        ->assertConflict()
        ->assertJsonPath('code', 'INCIDENT_FINALIZED');
});

it('el agente no asigna a otros ni trabaja lo que no es suyo', function (): void {
    $incident = reported();
    actingAsDemo('agente1@trazo.test');

    $this->putJson("/api/v1/incidents/{$incident['id']}/assignee", ['assignee' => demoUlid('agente2@trazo.test'), 'version' => 1])->assertForbidden();
    moveTo($incident, 'in_progress')->assertForbidden();
});

it('el supervisor asigna y quita la asignación, y cada cambio queda registrado', function (): void {
    $incident = reported();
    actingAsDemo('supervisor@trazo.test');

    $assigned = $this->putJson("/api/v1/incidents/{$incident['id']}/assignee", ['assignee' => demoUlid('agente2@trazo.test'), 'version' => 1])->json('data');
    $this->putJson("/api/v1/incidents/{$incident['id']}/assignee", ['assignee' => null, 'version' => $assigned['version']])
        ->assertOk()->assertJsonPath('data.assignee', null);

    $types = collect($this->getJson("/api/v1/incidents/{$incident['id']}/activities")->json('data'))->pluck('type.code')->all();
    expect($types)->toBe(['unassigned', 'assigned', 'created']);
});

it('elimina con borrado lógico y restaura; el solicitante no puede hacer ninguna de las dos', function (): void {
    $incident = reported();
    $this->deleteJson("/api/v1/incidents/{$incident['id']}")->assertForbidden();

    actingAsDemo('supervisor@trazo.test');
    $this->deleteJson("/api/v1/incidents/{$incident['id']}")->assertNoContent();
    $this->getJson("/api/v1/incidents/{$incident['id']}")->assertNotFound();
    expect(DB::table('incidents')->whereNotNull('deleted_at')->count())->toBe(1);

    $this->postJson("/api/v1/incidents/{$incident['id']}/restore")->assertOk()->assertJsonPath('data.deleted_at', null);
});

it('el detalle y el historial hacen las mismas consultas con poco o mucho historial (sin N+1)', function (): void {
    $incident = reported();
    actingAsDemo('supervisor@trazo.test');
    $queries = function () use ($incident): int {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->getJson("/api/v1/incidents/{$incident['id']}")->assertOk();
        $this->getJson("/api/v1/incidents/{$incident['id']}/activities")->assertOk();

        return count(DB::getQueryLog());
    };
    $before = $queries();

    $version = $incident['version'];
    foreach (['agente1@trazo.test', 'agente2@trazo.test', 'agente3@trazo.test', 'agente1@trazo.test'] as $agent) {
        $version = $this->putJson("/api/v1/incidents/{$incident['id']}/assignee", ['assignee' => demoUlid($agent), 'version' => $version])->json('data.version');
    }

    expect($queries())->toBe($before);
});
