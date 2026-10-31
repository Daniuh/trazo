<?php

declare(strict_types=1);

// Reglas de negocio de los incidentes que no dependen de quién actúa: vencimiento sugerido, avance del plazo y fecha
// de resolución.
// Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Domain;

use DateTimeImmutable;

final class IncidentRules
{
    // Al reabrir, lo que tiene a alguien asignado vuelve a trabajarse; lo que no, vuelve al estado inicial
    public const REOPEN_ASSIGNED_STATUS = 'in_progress';

    /** Si quien crea no indica vencimiento, se calcula con el tiempo de resolución de la prioridad. */
    public function dueDate(?DateTimeImmutable $requested, int $resolutionMinutes, DateTimeImmutable $now): DateTimeImmutable
    {
        return $requested ?? $now->modify("+{$resolutionMinutes} minutes");
    }

    /** "Vencido" no se guarda: es pendiente y su vencimiento ya pasó. */
    public function isOverdue(StatusFlags $status, DateTimeImmutable $dueDate, DateTimeImmutable $now): bool
    {
        return $status->isPending && $dueDate < $now;
    }

    /**
     * Cómo va con su plazo. El avance es el tiempo transcurrido desde que se creó sobre el plazo total; si el plazo es
     * nulo o negativo (vencimiento anterior a la creación), ya está gastado.
     */
    public function deadline(
        StatusFlags $status,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $dueDate,
        ?DateTimeImmutable $resolvedAt,
        DueWindow $window,
    ): Deadline {
        $bucket = $window->bucket($status, $dueDate);
        if ($bucket === DueBucket::Finished) {
            return new Deadline($bucket, null, $resolvedAt === null ? null : $resolvedAt <= $dueDate);
        }

        $total = $dueDate->getTimestamp() - $createdAt->getTimestamp();
        $elapsed = $window->now->getTimestamp() - $createdAt->getTimestamp();
        $used = $total <= 0 ? 100 : (int) round($elapsed / $total * 100);

        return new Deadline($bucket, max(0, min(100, $used)), null);
    }

    /**
     * Fecha de resolución al cambiar de estado, decidida por los datos del catálogo y no por códigos fijos:
     * volver a un estado pendiente la borra, llegar a uno resuelto la marca y cerrar o cancelar la conserva.
     */
    public function resolvedAt(StatusFlags $target, ?DateTimeImmutable $current, DateTimeImmutable $now): ?DateTimeImmutable
    {
        return match (true) {
            $target->isPending => null,
            $target->isFinal => $current,
            default => $now,
        };
    }
}
