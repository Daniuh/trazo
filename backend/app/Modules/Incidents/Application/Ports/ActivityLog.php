<?php

declare(strict_types=1);

// Historial de cambios de un incidente: cada escritura deja su registro, con valores anteriores y nuevos.
// Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Application\Ports;

use App\Modules\Incidents\Application\Data\RequestContext;

interface ActivityLog
{
    /**
     * @param  array<string, mixed>|null  $changes  Ej.: ['title' => ['from' => 'A', 'to' => 'B']]
     */
    public function record(int $incidentId, int $userId, string $type, ?array $changes, RequestContext $context): void;
}
