<?php

declare(strict_types=1);

// Reglas de fechas: vencimiento sugerido, "vencido" y fecha de resolución según el estado.
// Documentado en docs/backend/incidents.md

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
