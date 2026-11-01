<?php

declare(strict_types=1);

// Avisos de coordinación (ADR-0085): nuevo sin asignar, crítico reportado, reapertura pendiente y vencido. Van por
// permiso (incidents.assign, incidents.reopen.approve), nunca a quien hizo la acción y uno solo por persona.
// Documentado en docs/backend/notifications.md

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function coordinationTypes(string $email): array
{
    actingAsDemo($email);

    return array_column(test()->getJson('/api/v1/notifications?category=activity')->assertOk()->json('data'), 'type');
}

function reportIncident(string $email, string $priority, ?string $assignee = null): array
{
    actingAsDemo($email);

    return test()->postJson('/api/v1/incidents', array_filter([
        'title' => 'El servidor de archivos no responde',
        'description' => 'Nadie puede abrir las carpetas compartidas.',
        'priority' => $priority,
        'assignee' => $assignee,
    ]))->assertCreated()->json('data');
}

it('un incidente nuevo sin asignar avisa a quienes pueden asignar, no a los agentes', function (): void {
    reportIncident('solicitante1@trazo.test', 'low');

    actingAsDemo('supervisor@trazo.test');
    $item = $this->getJson('/api/v1/notifications?category=activity')->json('data.0');
    expect($item)->toMatchArray(['type' => 'unassigned_created', 'unassigned' => true, 'actor' => ['name' => 'Juan Pérez']])
        ->and(coordinationTypes('superadmin@trazo.test'))->toBe(['unassigned_created'])
        ->and(coordinationTypes('agente1@trazo.test'))->toBe([])
        ->and(coordinationTypes('admin@trazo.test'))->toBe([]);
});

it('un crítico sin asignar llega como un solo aviso de crítico', function (): void {
    reportIncident('solicitante1@trazo.test', 'critical');

    actingAsDemo('supervisor@trazo.test');
    expect($this->getJson('/api/v1/notifications?category=activity')->json('data'))->toHaveCount(1)
        ->and($this->getJson('/api/v1/notifications?category=activity')->json('data.0'))
        ->toMatchArray(['type' => 'critical_created', 'unassigned' => true]);
});

it('un crítico asignado avisa a quien lo atiende y a quienes coordinan, no a quien lo creó', function (): void {
    reportIncident('supervisor@trazo.test', 'critical', demoUlid('agente1@trazo.test'));

    actingAsDemo('agente1@trazo.test');
    expect($this->getJson('/api/v1/notifications?category=activity')->json('data'))->toHaveCount(1)
        ->and($this->getJson('/api/v1/notifications?category=activity')->json('data.0'))
        ->toMatchArray(['type' => 'critical_created', 'unassigned' => false])
        ->and(coordinationTypes('superadmin@trazo.test'))->toBe(['critical_created'])
        ->and(coordinationTypes('supervisor@trazo.test'))->toBe([]);
});

it('va por permiso: a quien se le concede asignar le llega y a quien se le quita, no', function (): void {
    $permission = DB::table('permissions')->where('code', 'incidents.assign')->value('id');
    $id = fn (string $email) => DB::table('users')->where('email', $email)->value('id');
    $by = $id('superadmin@trazo.test');
    DB::table('user_permissions')->insert([
        ['user_id' => $id('admin@trazo.test'), 'permission_id' => $permission, 'effect' => 'grant', 'granted_by' => $by],
        ['user_id' => $id('supervisor@trazo.test'), 'permission_id' => $permission, 'effect' => 'deny', 'granted_by' => $by],
    ]);
    reportIncident('solicitante1@trazo.test', 'low');

    expect(coordinationTypes('admin@trazo.test'))->toBe(['unassigned_created'])
        ->and(coordinationTypes('supervisor@trazo.test'))->toBe([]);
});

it('una solicitud de reapertura avisa a quienes pueden aprobarla', function (): void {
    $incident = reportIncident('solicitante1@trazo.test', 'low');
    actingAsDemo('supervisor@trazo.test');
    $this->putJson("/api/v1/incidents/{$incident['id']}/status", ['status' => 'cancelled', 'version' => $incident['version'], 'note' => 'Duplicado'])->assertOk();
    actingAsDemo('solicitante1@trazo.test');
    $this->postJson("/api/v1/incidents/{$incident['id']}/reopen-request", ['reason' => 'No era duplicado'])->assertCreated();

    expect(coordinationTypes('supervisor@trazo.test'))->toBe(['reopen_requested', 'unassigned_created'])
        ->and(coordinationTypes('superadmin@trazo.test'))->toBe(['reopen_requested', 'unassigned_created'])
        ->and(coordinationTypes('agente1@trazo.test'))->toBe([]);
});

it('un vencimiento avisa una sola vez a quien lo atiende y a quienes coordinan, y otra vez si vuelve a vencer', function (): void {
    $incident = reportIncident('supervisor@trazo.test', 'low', demoUlid('agente1@trazo.test'));
    $due = fn (string $moment) => DB::table('incidents')->where('ulid', $incident['id'])->update(['due_date' => $moment]);

    $due(now()->subMinutes(10)->format('Y-m-d H:i:s'));
    $this->artisan('notifications:overdue')->expectsOutput('Incidentes vencidos avisados: 1')->assertSuccessful();
    $this->artisan('notifications:overdue')->expectsOutput('Incidentes vencidos avisados: 0')->assertSuccessful();

    actingAsDemo('agente1@trazo.test');
    expect($this->getJson('/api/v1/notifications?category=activity')->json('data.0'))
        ->toMatchArray(['type' => 'incident_overdue', 'unassigned' => false, 'actor' => null])
        ->and(coordinationTypes('superadmin@trazo.test'))->toBe(['incident_overdue'])
        // El supervisor lo creó y asignó, pero el vencimiento no lo hizo nadie: también le llega
        ->and(coordinationTypes('supervisor@trazo.test'))->toBe(['incident_overdue']);

    // Le dan más plazo y vuelve a vencer después del primer aviso: es otro vencimiento
    $this->travel(5)->minutes();
    $due(now()->subSeconds(30)->format('Y-m-d H:i:s'));
    $this->artisan('notifications:overdue')->expectsOutput('Incidentes vencidos avisados: 1')->assertSuccessful();
    expect(coordinationTypes('agente1@trazo.test'))->toBe(['incident_overdue', 'incident_overdue', 'incident_assigned']);
});

it('no avisa de lo resuelto, lo eliminado ni lo que venció hace más de un día', function (): void {
    $old = reportIncident('solicitante1@trazo.test', 'low');
    $resolved = reportIncident('solicitante1@trazo.test', 'low');
    $deleted = reportIncident('solicitante1@trazo.test', 'low');
    DB::table('incidents')->where('ulid', $old['id'])->update(['due_date' => now()->subDays(2)]);
    DB::table('incidents')->whereIn('ulid', [$resolved['id'], $deleted['id']])->update(['due_date' => now()->subMinutes(5)]);
    DB::table('incidents')->where('ulid', $resolved['id'])->update(['status_id' => DB::table('statuses')->where('code', 'resolved')->value('id')]);
    DB::table('incidents')->where('ulid', $deleted['id'])->update(['deleted_at' => now()]);

    $this->artisan('notifications:overdue')->expectsOutput('Incidentes vencidos avisados: 0')->assertSuccessful();
});
