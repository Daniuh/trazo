<?php

declare(strict_types=1);

// Aviso de que un incidente se creó, cambió, se eliminó o se restauró. Sale solo cuando la transacción se confirma,
// así nadie reacciona a un cambio que al final se deshizo. Lo escuchan la caché del dashboard y el tiempo real, que
// necesita saber quién lo creó y a quién estaba y queda asignado para avisar a los canales correctos.
// Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Application\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

final readonly class IncidentChanged implements ShouldDispatchAfterCommit
{
    public function __construct(
        public int $incidentId,
        public string $ulid,
        public IncidentChange $change,
        public int $createdBy,
        public ?int $assigneeBefore,
        public ?int $assigneeAfter,
    ) {}

    public function assigneeChanged(): bool
    {
        return $this->assigneeBefore !== $this->assigneeAfter;
    }
}
