<?php

declare(strict_types=1);

// Catálogos de prioridades y estados, con las transiciones permitidas. Solo devuelve valores activos.
// Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Application\Ports;

use App\Modules\Incidents\Application\Data\PriorityInfo;
use App\Modules\Incidents\Domain\StatusFlags;

interface IncidentCatalog
{
    /**
     * @return array<string, PriorityInfo>
     */
    public function priorities(): array;

    /**
     * @return array<string, StatusFlags>
     */
    public function statuses(): array;

    public function priority(string $code): ?PriorityInfo;

    public function status(string $code): ?StatusFlags;

    public function initialStatus(): StatusFlags;

    /**
     * @return list<StatusFlags>
     */
    public function nextStatuses(int $statusId): array;
}
