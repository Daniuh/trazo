<?php

declare(strict_types=1);

// "Mis incidentes" por la API: solo los asignados a quien pregunta, en curso o terminados hace poco, ordenados por
// vencimiento y con los estados a los que se puede mover cada uno. Documentado en docs/backend/incidents.md

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

// Incidente del solicitante 1 asignado por el supervisor; devuelve su id y versión
function boardIncident(string $title, string $assignee, ?string $due = null): array
{
    actingAsDemo('solicitante1@trazo.test');
    $incident = test()->postJson('/api/v1/incidents', array_filter([
        'title' => $title, 'description' => 'Para el tablero.', 'priority' => 'medium', 'due_date' => $due,
    ]))->json('data');

    actingAsDemo('supervisor@trazo.test');

    return test()->putJson("/api/v1/incidents/{$incident['id']}/assignee", ['assignee' => demoUlid($assignee), 'version' => $incident['version']])->json('data');
}

it('trae solo lo asignado a quien pregunta, por vencimiento y con sus próximos estados', function (): void {
    $later = boardIncident('Impresora del piso 2', 'agente1@trazo.test', now()->addDays(3)->toIso8601String());
    $sooner = boardIncident('Correo no sincroniza', 'agente1@trazo.test', now()->addDay()->toIso8601String());
    boardIncident('Teclado dañado', 'agente2@trazo.test');

    actingAsDemo('agente1@trazo.test');
    $board = $this->getJson('/api/v1/incidents/mine')->assertOk()
        ->assertJsonPath('meta.truncated', false)
        ->assertJsonPath('meta.finished_days', 7)
        ->json('data');

    expect(array_column($board, 'id'))->toBe([$sooner['id'], $later['id']])
        ->and(array_column($board[0]['next_statuses'], 'code'))->toBe(['in_progress', 'cancelled'])
        ->and($board[0]['actions']['change_status'])->toBeTrue();
});

it('los terminados se ven 7 días y los eliminados nunca', function (): void {
    $closed = boardIncident('Pantalla parpadea', 'agente1@trazo.test');
    $old = boardIncident('Mouse lento', 'agente1@trazo.test');
    $deleted = boardIncident('Duplicado', 'agente1@trazo.test');

    actingAsDemo('supervisor@trazo.test');
    $this->putJson("/api/v1/incidents/{$closed['id']}/status", ['status' => 'cancelled', 'version' => $closed['version'], 'note' => 'No aplica'])->assertOk();
    $this->putJson("/api/v1/incidents/{$old['id']}/status", ['status' => 'cancelled', 'version' => $old['version'], 'note' => 'No aplica'])->assertOk();
    DB::table('incidents')->where('ulid', $old['id'])->update(['updated_at' => now()->subDays(8)]);
    $this->deleteJson("/api/v1/incidents/{$deleted['id']}")->assertNoContent();

    actingAsDemo('agente1@trazo.test');
    $board = $this->getJson('/api/v1/incidents/mine')->assertOk()->json('data');

    expect(array_column($board, 'id'))->toBe([$closed['id']])
        ->and($board[0]['next_statuses'])->toBe([]);
});

it('solo quien puede tener incidentes asignados tiene tablero', function (): void {
    actingAsDemo('solicitante1@trazo.test');
    $this->getJson('/api/v1/incidents/mine')->assertForbidden();

    actingAsDemo('admin@trazo.test');
    $this->getJson('/api/v1/incidents/mine')->assertForbidden();
});

it('lee el tablero con las mismas consultas, tenga los incidentes que tenga', function (): void {
    $queries = function (): int {
        actingAsDemo('agente1@trazo.test');
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->getJson('/api/v1/incidents/mine')->assertOk();
        DB::disableQueryLog();

        return count(DB::getQueryLog());
    };

    boardIncident('Primer pendiente', 'agente1@trazo.test');
    // La primera petición llena cachés (catálogos, permisos); se mide desde la segunda
    $queries();
    $one = $queries();
    foreach (['Segundo pendiente', 'Tercer pendiente', 'Cuarto pendiente'] as $title) {
        boardIncident($title, 'agente1@trazo.test');
    }

    expect($queries())->toBe($one);
});
