<?php

declare(strict_types=1);

// Reglas de fechas: vencimiento sugerido, "vencido", avance del plazo y fecha de resolución según el estado.
// Documentado en docs/backend/incidents.md

use App\Modules\Incidents\Domain\Deadline;
use App\Modules\Incidents\Domain\DueBucket;
use App\Modules\Incidents\Domain\DueWindow;
use App\Modules\Incidents\Domain\IncidentRules;
use App\Modules\Incidents\Domain\StatusFlags;

$now = new DateTimeImmutable('2026-10-22 10:00:00');
$status = fn (bool $pending, bool $final = false): StatusFlags => new StatusFlags(1, 'x', 'X', false, $pending, $final);

it('respeta el vencimiento indicado y, si no hay, lo calcula con la prioridad', function () use ($now): void {
    $rules = new IncidentRules;
    $requested = new DateTimeImmutable('2026-11-01 08:00:00');

    expect($rules->dueDate($requested, 240, $now))->toBe($requested)
        ->and($rules->dueDate(null, 240, $now)->format('Y-m-d H:i'))->toBe('2026-10-22 14:00');
});

it('solo un pendiente con la fecha pasada está vencido', function () use ($now, $status): void {
    $rules = new IncidentRules;
    $yesterday = $now->modify('-1 day');

    expect($rules->isOverdue($status(pending: true), $yesterday, $now))->toBeTrue()
        ->and($rules->isOverdue($status(pending: false), $yesterday, $now))->toBeFalse()
        ->and($rules->isOverdue($status(pending: true), $now->modify('+1 hour'), $now))->toBeFalse();
});

it('marca la resolución al resolver, la borra al reabrir y la conserva al cerrar', function () use ($now, $status): void {
    $rules = new IncidentRules;
    $earlier = $now->modify('-2 hours');

    expect($rules->resolvedAt($status(pending: false), null, $now))->toBe($now)
        ->and($rules->resolvedAt($status(pending: true), $earlier, $now))->toBeNull()
        ->and($rules->resolvedAt($status(pending: false, final: true), $earlier, $now))->toBe($earlier);
});

it('agrupa por vencimiento con el día del negocio y calcula el avance del plazo', function () use ($status): void {
    $rules = new IncidentRules;
    // 10:00 en Bogotá: hoy termina a las 05:00 UTC del 23 y la semana a las 05:00 UTC del 30
    $window = DueWindow::at(new DateTimeImmutable('2026-10-22 15:00:00 UTC'), new DateTimeZone('America/Bogota'));
    $created = new DateTimeImmutable('2026-10-22 05:00:00 UTC');
    $deadline = fn (string $due, bool $pending = true, ?string $resolved = null) => $rules->deadline(
        $status($pending), $created, new DateTimeImmutable("{$due} UTC"), $resolved ? new DateTimeImmutable("{$resolved} UTC") : null, $window,
    );

    expect($deadline('2026-10-22 14:00:00'))->toEqual(new Deadline(DueBucket::Overdue, 100, null))
        ->and($deadline('2026-10-23 04:59:59')->bucket)->toBe(DueBucket::Today)
        ->and($deadline('2026-10-23 05:00:00')->bucket)->toBe(DueBucket::Week)
        ->and($deadline('2026-10-30 05:00:00')->bucket)->toBe(DueBucket::Later)
        ->and($deadline('2026-10-23 01:00:00')->timeUsed)->toBe(50)
        // Un vencimiento anterior a la creación ya está gastado
        ->and($deadline('2026-10-22 04:00:00')->timeUsed)->toBe(100)
        ->and($deadline('2026-10-20 12:00:00', false, '2026-10-19 12:00:00'))->toEqual(new Deadline(DueBucket::Finished, null, true))
        ->and($deadline('2026-10-20 12:00:00', false, '2026-10-21 12:00:00')->resolvedOnTime)->toBeFalse()
        ->and($deadline('2026-10-20 12:00:00', false)->resolvedOnTime)->toBeNull();
});
