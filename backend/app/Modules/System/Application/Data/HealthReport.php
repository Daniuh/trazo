<?php

declare(strict_types=1);

// El panel de salud ya interpretado: cada componente con su estado según HealthRules y el estado general, que es el
// peor de todos. Documentado en docs/backend/system.md

namespace App\Modules\System\Application\Data;

use App\Modules\System\Domain\HealthRules;
use App\Modules\System\Domain\HealthStatus;
use DateTimeImmutable;

final readonly class HealthReport
{
    /**
     * @param  list<ComponentHealth>  $components
     */
    public function __construct(
        public DateTimeImmutable $checkedAt,
        public HealthStatus $status,
        public array $components,
        public ?MaintenanceState $maintenance,
    ) {}

    public static function from(HealthMeasurements $m, ?MaintenanceState $maintenance): self
    {
        $components = [
            new ComponentHealth('database', HealthRules::latency($m->databaseMs), ['latency_ms' => $m->databaseMs]),
            new ComponentHealth('redis', HealthRules::latency($m->redisMs), ['latency_ms' => $m->redisMs]),
            new ComponentHealth('queue', HealthRules::queue($m->pendingJobs, $m->oldestPendingSeconds, $m->failedJobs), [
                'pending' => $m->pendingJobs,
                'oldest_seconds' => $m->oldestPendingSeconds,
                'failed' => $m->failedJobs,
            ]),
            new ComponentHealth('worker', HealthRules::heartbeat($m->workerSecondsAgo), ['seconds_ago' => $m->workerSecondsAgo]),
            new ComponentHealth('scheduler', HealthRules::heartbeat($m->schedulerSecondsAgo), ['seconds_ago' => $m->schedulerSecondsAgo]),
            new ComponentHealth('realtime', HealthRules::reachable($m->realtimeReachable), []),
            new ComponentHealth('storage', HealthRules::disk($m->diskFreeBytes, $m->diskTotalBytes), [
                'free_bytes' => $m->diskFreeBytes,
                'total_bytes' => $m->diskTotalBytes,
            ]),
        ];

        return new self(
            $m->at,
            HealthStatus::worst(...array_map(fn (ComponentHealth $component): HealthStatus => $component->status, $components)),
            $components,
            $maintenance,
        );
    }
}
