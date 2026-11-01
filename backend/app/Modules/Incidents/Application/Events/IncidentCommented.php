<?php

declare(strict_types=1);

// Aviso de que la conversación de un incidente cambió: llegó un mensaje o se borró uno. Sale solo cuando la
// transacción se confirma. Lo escuchan el tiempo real, que avisa según el creador y el asignado, y las
// notificaciones, que avisan a quienes participan.
// Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Application\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

final readonly class IncidentCommented implements ShouldDispatchAfterCommit
{
    public function __construct(
        public int $incidentId,
        public string $incidentUlid,
        public int $commentId,
        public int $createdBy,
        public ?int $assignedTo,
        public int $authorId,
        public bool $removed,
    ) {}
}
