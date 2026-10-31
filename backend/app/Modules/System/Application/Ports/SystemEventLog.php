<?php

declare(strict_types=1);

// Lectura de los eventos del sistema y cambio de su estado. El registro lo hace el puerto compartido SystemEvents.
// Documentado en docs/backend/system.md

namespace App\Modules\System\Application\Ports;

use App\Modules\System\Application\Data\EventCriteria;
use App\Modules\System\Application\Data\EventPage;
use App\Modules\System\Application\Data\EventRecord;
use App\Modules\System\Application\Data\EventType;
use App\Modules\System\Domain\EventStatus;
use DateTimeImmutable;

interface SystemEventLog
{
    public function page(EventCriteria $criteria): EventPage;

    public function find(int $id): ?EventRecord;

    public function changeStatus(int $id, EventStatus $status, int $byUserId): void;

    /**
     * @return list<EventType>
     */
    public function types(): array;

    // Borra los resueltos antes de esa fecha; devuelve cuántos
    public function pruneResolvedBefore(DateTimeImmutable $limit): int;
}
