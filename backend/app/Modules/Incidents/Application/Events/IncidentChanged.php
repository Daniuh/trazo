<?php

declare(strict_types=1);

// Aviso de que un incidente se creó, cambió, se eliminó o se restauró. Sale solo cuando la transacción se confirma,
// así nadie reacciona a un cambio que al final se deshizo. Lo escuchan, por ejemplo, la caché del dashboard.
// Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Application\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

final readonly class IncidentChanged implements ShouldDispatchAfterCommit
{
    public function __construct(public int $incidentId) {}
}
