<?php

declare(strict_types=1);

// Solicitudes de reapertura: se abren con un motivo y se deciden una sola vez.
// Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Application\Ports;

use App\Modules\Shared\Domain\Exceptions\ConflictException;

interface ReopenRequests
{
    /**
     * @throws ConflictException si el incidente ya tiene una solicitud pendiente
     */
    public function open(int $incidentId, int $requestedBy, string $reason): void;

    /**
     * Aprueba o rechaza la solicitud pendiente. False si ya no había ninguna (otra persona la decidió antes).
     */
    public function decide(int $incidentId, int $decidedBy, bool $approved, ?string $note): bool;
}
