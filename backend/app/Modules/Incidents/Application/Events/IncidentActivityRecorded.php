<?php

declare(strict_types=1);

// Aviso de que se anotó algo en el historial de un incidente (asignado, cambio de estado, reabierto...). Sale solo
// cuando la transacción se confirma. Lo escuchan las notificaciones, que deciden a quién avisar según el tipo.
// Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Application\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

final readonly class IncidentActivityRecorded implements ShouldDispatchAfterCommit
{
    public function __construct(
        public int $activityId,
        public int $incidentId,
        public string $type,
        public int $actorId,
    ) {}
}
