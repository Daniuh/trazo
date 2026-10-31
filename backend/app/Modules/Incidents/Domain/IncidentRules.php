<?php

declare(strict_types=1);

// Reglas de negocio de los incidentes que no dependen de quién actúa: vencimiento sugerido y fecha de resolución.
// Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Domain;

use DateTimeImmutable;

final class IncidentRules
{
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
