<?php

declare(strict_types=1);

// Listado agrupado: orden por grupo, total de cada grupo con los filtros y avance del plazo de cada incidente.
// Documentado en docs/backend/incidents.md

use App\Modules\Users\Infrastructure\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// 10:00 en Bogotá: "hoy" termina a las 05:00 UTC del día siguiente
beforeEach(fn () => $this->travelTo(new DateTimeImmutable('2026-10-22 15:00:00 UTC')));

function groupedList(string $query): array
{
    return test()->getJson("/api/v1/incidents?{$query}")->assertOk()->json();
}

it('agrupa por vencimiento en el orden de los grupos y cuenta cada uno', function (): void {
    incidentWith(['code' => 'TZ-70-0001', 'due_date' => '2026-10-30 12:00:00'], 'open');
    incidentWith(['code' => 'TZ-70-0002', 'due_date' => '2026-10-21 12:00:00'], 'in_progress');
    incidentWith(['code' => 'TZ-70-0003', 'due_date' => '2026-10-23 04:00:00'], 'on_hold');
    incidentWith(['code' => 'TZ-70-0004', 'due_date' => '2026-10-25 12:00:00'], 'open');
    incidentWith(['code' => 'TZ-70-0005', 'due_date' => '2026-10-21 12:00:00'], 'closed');
    incidentWith(['code' => 'TZ-70-0006', 'due_date' => '2026-10-20 12:00:00'], 'open');
    actingAsDemo('supervisor@trazo.test');

    $page = groupedList('group_by=due&sort=code&per_page=4');

    expect(array_column($page['data'], 'code'))->toBe(['TZ-70-0002', 'TZ-70-0006', 'TZ-70-0003', 'TZ-70-0004'])
        ->and(array_column($page['data'], 'deadline'))->sequence(
            fn ($deadline) => $deadline->bucket->toBe('overdue'),
            fn ($deadline) => $deadline->bucket->toBe('overdue'),
            fn ($deadline) => $deadline->bucket->toBe('today'),
            fn ($deadline) => $deadline->bucket->toBe('week'),
        )
        // El total de cada grupo de la página, con estos filtros; el total general suma todos
        ->and($page['meta']['groups'])->toBe([
            ['key' => 'overdue', 'total' => 2],
            ['key' => 'today', 'total' => 1],
            ['key' => 'week', 'total' => 1],
        ])
        ->and($page['meta']['total'])->toBe(6);

    // La segunda página empieza en el grupo siguiente y sigue en el otro
    $next = groupedList('group_by=due&sort=code&page=2&per_page=4');
    expect(array_column($next['data'], 'code'))->toBe(['TZ-70-0001', 'TZ-70-0005'])
        ->and($next['meta']['groups'])->toBe([['key' => 'later', 'total' => 1], ['key' => 'finished', 'total' => 1]]);

    // Una página que empieza a mitad de un grupo
    expect(array_column(groupedList('group_by=due&sort=code&page=2&per_page=1')['data'], 'code'))->toBe(['TZ-70-0006'])
        ->and(groupedList('group_by=due&page=9&per_page=4')['data'])->toBe([]);
});

it('agrupa por estado, prioridad y asignado, y respeta el orden elegido dentro de cada grupo', function (): void {
    $agent = User::query()->where('email', 'agente1@trazo.test')->firstOrFail();
    incidentWith(['code' => 'TZ-71-0001'], 'in_progress', 'low');
    incidentWith(['code' => 'TZ-71-0002', 'assigned_to' => $agent->id], 'open', 'critical');
    incidentWith(['code' => 'TZ-71-0003'], 'open', 'low');
    actingAsDemo('supervisor@trazo.test');

    expect(array_column(groupedList('group_by=status&sort=-code')['data'], 'code'))->toBe(['TZ-71-0003', 'TZ-71-0002', 'TZ-71-0001'])
        ->and(array_column(groupedList('group_by=priority&sort=code')['data'], 'code'))->toBe(['TZ-71-0002', 'TZ-71-0001', 'TZ-71-0003'])
        ->and(array_column(groupedList('group_by=assignee&sort=code')['data'], 'code'))->toBe(['TZ-71-0001', 'TZ-71-0003', 'TZ-71-0002'])
        ->and(groupedList('group_by=assignee&sort=code')['meta']['groups'])->toEqualCanonicalizing([
            ['key' => 'none', 'total' => 2],
            ['key' => $agent->ulid, 'total' => 1],
        ])
        ->and(groupedList('group_by=status&filter[priority]=low')['meta']['groups'])->toEqualCanonicalizing([
            ['key' => 'open', 'total' => 1],
            ['key' => 'in_progress', 'total' => 1],
        ]);
});

it('calcula el avance del plazo de los pendientes y si los terminados se resolvieron a tiempo', function (): void {
    incidentWith(['code' => 'TZ-72-0001', 'created_at' => '2026-10-22 05:00:00', 'due_date' => '2026-10-23 04:00:00'], 'open');
    incidentWith(['code' => 'TZ-72-0002', 'due_date' => '2026-10-20 12:00:00', 'resolved_at' => '2026-10-19 12:00:00'], 'resolved');
    incidentWith(['code' => 'TZ-72-0003', 'due_date' => '2026-10-20 12:00:00', 'resolved_at' => '2026-10-21 12:00:00'], 'closed');
    incidentWith(['code' => 'TZ-72-0004', 'due_date' => '2026-10-20 12:00:00', 'resolved_at' => null], 'cancelled');
    actingAsDemo('supervisor@trazo.test');

    expect(array_column(groupedList('sort=code')['data'], 'deadline'))->toBe([
        ['bucket' => 'today', 'time_used' => 43, 'resolved_on_time' => null],
        ['bucket' => 'finished', 'time_used' => null, 'resolved_on_time' => true],
        ['bucket' => 'finished', 'time_used' => null, 'resolved_on_time' => false],
        ['bucket' => 'finished', 'time_used' => null, 'resolved_on_time' => null],
    ]);
});

it('sin grupo no cuenta grupos y rechaza un grupo que no está en la lista', function (): void {
    incidentWith();
    actingAsDemo('supervisor@trazo.test');

    expect(groupedList('')['meta']['groups'])->toBe([]);
    $this->getJson('/api/v1/incidents?group_by=title')->assertUnprocessable()->assertJsonPath('errors.group_by.0.code', 'INVALID_OPTION');
});

it('la exportación sigue el mismo orden por grupo que el listado', function (): void {
    incidentWith(['code' => 'TZ-73-0001', 'due_date' => '2026-10-30 12:00:00'], 'open');
    incidentWith(['code' => 'TZ-73-0002', 'due_date' => '2026-10-21 12:00:00'], 'open');
    actingAsDemo('supervisor@trazo.test');

    $csv = $this->get('/api/v1/incidents/export?group_by=due&sort=code')->assertOk()->streamedContent();

    expect(strpos($csv, 'TZ-73-0002'))->toBeLessThan(strpos($csv, 'TZ-73-0001'));
});
