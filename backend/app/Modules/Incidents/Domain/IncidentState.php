<?php

declare(strict_types=1);

// Lo que las reglas necesitan saber de un incidente: de quién es, quién lo atiende y en qué punto del ciclo está.
// Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Domain;

final readonly class IncidentState
{
    public function __construct(
        public int $createdBy,
        public ?int $assignedTo,
        public bool $statusIsInitial,
        public bool $statusIsFinal,
        public bool $deleted,
        // Quién pidió reabrirlo, si hay una solicitud pendiente
        public ?int $reopenRequestedBy = null,
    ) {}
}
