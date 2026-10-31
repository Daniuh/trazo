<?php

declare(strict_types=1);

// Reglas de la grilla del dashboard: diseño original sin huecos, topes, choques y porcentajes que suman 100.
// Documentado en docs/backend/dashboard.md

use App\Modules\Dashboard\Domain\DashboardGrid;
use App\Modules\Dashboard\Domain\Percentages;
use App\Modules\Dashboard\Domain\Placement;
use App\Modules\Dashboard\Domain\WidgetCode;
use App\Modules\Dashboard\Domain\WidgetDefinition;
use App\Modules\Dashboard\Domain\WidgetKind;
use App\Modules\Dashboard\Domain\WidgetLayout;
use App\Modules\Shared\Domain\Exceptions\RuleViolationException;

function gridWidget(WidgetCode $code, int $w, int $h, int $maxW = 12, int $minW = 2): WidgetDefinition
{
    return new WidgetDefinition($code, $code->value, '', WidgetKind::Chart, null, $minW, $maxW, 2, 6, $w, $h);
}

function gridLayout(WidgetCode $code, int $x, int $y, int $w, int $h, bool $visible = true): WidgetLayout
{
    return new WidgetLayout($code, new Placement($x, $y, $w, $h), $visible);
}

/**
 * @param  list<WidgetLayout>  $layouts
 * @param  list<WidgetDefinition>  $definitions
 */
function gridViolation(array $layouts, array $definitions): string
{
    $allowed = [];
    foreach ($definitions as $definition) {
        $allowed[$definition->code->value] = $definition;
    }

    try {
        DashboardGrid::validate($layouts, $allowed);
    } catch (RuleViolationException $exception) {
        return $exception->errorCode();
    }

    return 'OK';
}

it('arma el diseño original fila por fila y el último de cada fila crece para no dejar huecos', function (): void {
    $placements = DashboardGrid::defaults([
        gridWidget(WidgetCode::TotalIncidents, 3, 2, maxW: 6),
        gridWidget(WidgetCode::PendingIncidents, 3, 2, maxW: 6),
        gridWidget(WidgetCode::OverdueIncidents, 3, 2, maxW: 6),
        gridWidget(WidgetCode::IncidentsByStatus, 6, 4),
    ]);

    expect($placements['overdue_incidents'])->toEqual(new Placement(6, 0, 6, 2))
        ->and($placements['incidents_by_status'])->toEqual(new Placement(0, 2, 12, 4));
});

it('no hace crecer un widget más allá de su tope', function (): void {
    $placements = DashboardGrid::defaults([gridWidget(WidgetCode::TotalIncidents, 3, 2, maxW: 4)]);

    expect($placements['total_incidents'])->toEqual(new Placement(0, 0, 4, 2));
});

it('acepta un diseño válido, con ocultos que se cruzan con visibles', function (): void {
    $definitions = [gridWidget(WidgetCode::TotalIncidents, 3, 2), gridWidget(WidgetCode::IncidentsByStatus, 6, 4)];

    expect(gridViolation([
        gridLayout(WidgetCode::TotalIncidents, 0, 0, 3, 2),
        gridLayout(WidgetCode::IncidentsByStatus, 0, 0, 6, 4, visible: false),
    ], $definitions))->toBe('OK');
});

it('rechaza widgets no disponibles, repetidos, fuera de la grilla, de tamaño inválido o que chocan', function (): void {
    $definitions = [gridWidget(WidgetCode::TotalIncidents, 3, 2, maxW: 6), gridWidget(WidgetCode::IncidentsByStatus, 6, 4)];

    expect(gridViolation([gridLayout(WidgetCode::WorkloadByAgent, 0, 0, 6, 4)], $definitions))->toBe('WIDGET_NOT_AVAILABLE')
        ->and(gridViolation([gridLayout(WidgetCode::TotalIncidents, 0, 0, 3, 2), gridLayout(WidgetCode::TotalIncidents, 3, 0, 3, 2)], $definitions))->toBe('WIDGET_DUPLICATED')
        ->and(gridViolation([gridLayout(WidgetCode::TotalIncidents, 10, 0, 3, 2)], $definitions))->toBe('WIDGET_OUT_OF_GRID')
        ->and(gridViolation([gridLayout(WidgetCode::TotalIncidents, 0, 0, 8, 2)], $definitions))->toBe('WIDGET_SIZE_OUT_OF_RANGE')
        ->and(gridViolation([gridLayout(WidgetCode::TotalIncidents, 0, 0, 3, 2), gridLayout(WidgetCode::IncidentsByStatus, 2, 1, 6, 4)], $definitions))->toBe('WIDGETS_OVERLAP');
});

it('ajusta un lugar guardado a los topes actuales del widget', function (): void {
    $definition = gridWidget(WidgetCode::TotalIncidents, 3, 2, maxW: 4);

    expect(DashboardGrid::fit($definition, new Placement(9, 5, 6, 9)))->toEqual(new Placement(8, 5, 4, 6));
});

it('reparte porcentajes enteros que siempre suman 100', function (array $counts, array $expected): void {
    expect(Percentages::of($counts))->toBe($expected)->and(array_sum(Percentages::of($counts)))->toBeIn([0, 100]);
})->with([
    'tercios' => [[1, 1, 1], [34, 33, 33]],
    'sin datos' => [[0, 0], [0, 0]],
    'exactos' => [[1, 3], [25, 75]],
    'residuos' => [[155, 247, 145, 57], [26, 41, 24, 9]],
]);
