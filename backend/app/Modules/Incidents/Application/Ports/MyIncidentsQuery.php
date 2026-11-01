<?php

declare(strict_types=1);

// Lectura de "Mis incidentes": los asignados a una persona que siguen en curso y los terminados hace poco.
// Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Application\Ports;

use App\Modules\Incidents\Application\Data\IncidentRecord;
use DateTimeImmutable;

interface MyIncidentsQuery
{
    /**
     * Asignados a la persona y no eliminados: los que no están en un estado final y los que llegaron a uno desde
     * $finishedSince. Ordenados por vencimiento, hasta $limit.
     *
     * @return list<IncidentRecord>
     */
    public function assignedTo(int $userId, DateTimeImmutable $finishedSince, int $limit): array;
}
