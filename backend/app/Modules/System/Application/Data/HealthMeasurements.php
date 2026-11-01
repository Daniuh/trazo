<?php

declare(strict_types=1);

// Lo que se midió en una revisión de salud, sin interpretar: tiempos, tamaños y señales de vida. null significa que
// no respondió o que todavía no hay dato. Documentado en docs/backend/system.md

namespace App\Modules\System\Application\Data;

use DateTimeImmutable;

final readonly class HealthMeasurements
{
    public function __construct(
        public DateTimeImmutable $at,
        public ?float $databaseMs,
        public ?float $redisMs,
        public int $pendingJobs,
        public ?int $oldestPendingSeconds,
        public int $failedJobs,
        public ?int $workerSecondsAgo,
        public ?int $schedulerSecondsAgo,
        public bool $realtimeReachable,
        public int $diskFreeBytes,
        public int $diskTotalBytes,
    ) {}
}
