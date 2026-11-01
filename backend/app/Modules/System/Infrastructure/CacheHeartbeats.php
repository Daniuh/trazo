<?php

declare(strict_types=1);

// Señales de vida en la caché compartida (Redis): la hora de la última vez que cada proceso dio señales. La del
// worker la deja un job, así se comprueba que la cola de verdad se procesa. Documentado en docs/backend/system.md

namespace App\Modules\System\Infrastructure;

use App\Modules\System\Application\Ports\Heartbeats;
use Illuminate\Contracts\Cache\Repository;
use Throwable;

final readonly class CacheHeartbeats implements Heartbeats
{
    public function __construct(private Repository $cache) {}

    public function beat(string $process): void
    {
        $this->cache->forever($this->key($process), time());
    }

    public function requestWorkerBeat(): void
    {
        WorkerHeartbeat::dispatch();
    }

    public function secondsAgo(string $process): ?int
    {
        try {
            $at = $this->cache->get($this->key($process));
        } catch (Throwable) {
            return null;
        }

        return is_numeric($at) ? max(0, time() - (int) $at) : null;
    }

    private function key(string $process): string
    {
        return "system:heartbeat:{$process}";
    }
}
