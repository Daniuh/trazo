<?php

declare(strict_types=1);

// Mide cada pieza desde la app: una consulta a MySQL, un PING a Redis, el tamaño de la cola y su job más viejo, los
// jobs fallidos, las señales de vida, una conexión al puerto de Reverb y el disco del almacenamiento. Lo que no
// responde queda en null o false, nunca lanza. Documentado en docs/backend/system.md

namespace App\Modules\System\Infrastructure;

use App\Modules\System\Application\Data\HealthMeasurements;
use App\Modules\System\Application\Ports\HealthProbe;
use App\Modules\System\Application\Ports\Heartbeats;
use Closure;
use Illuminate\Queue\RedisQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Throwable;

final readonly class LaravelHealthProbe implements HealthProbe
{
    private const SOCKET_TIMEOUT_SECONDS = 1.0;

    public function __construct(
        private Heartbeats $heartbeats,
        private string $realtimeHost,
        private int $realtimePort,
    ) {}

    public function measure(): HealthMeasurements
    {
        [$pending, $oldest] = $this->queue();

        return new HealthMeasurements(
            now()->toDateTimeImmutable(),
            $this->timed(fn () => DB::select('select 1')),
            $this->timed(fn () => Redis::connection()->ping()),
            $pending,
            $oldest,
            $this->attempt(fn (): int => DB::table('failed_jobs')->count(), 0),
            $this->heartbeats->secondsAgo(Heartbeats::WORKER),
            $this->heartbeats->secondsAgo(Heartbeats::SCHEDULER),
            $this->reachable(),
            (int) $this->attempt(fn () => disk_free_space(storage_path()) ?: 0, 0),
            (int) $this->attempt(fn () => disk_total_space(storage_path()) ?: 0, 0),
        );
    }

    // Milisegundos que tardó, o null si falló
    private function timed(Closure $probe): ?float
    {
        $start = hrtime(true);

        return $this->attempt(function () use ($probe, $start): float {
            $probe();

            return round((hrtime(true) - $start) / 1e6, 1);
        }, null);
    }

    /**
     * Pendientes y antigüedad del más viejo. Solo Redis informa la antigüedad.
     *
     * @return array{int, int|null}
     */
    private function queue(): array
    {
        return $this->attempt(function (): array {
            $queue = Queue::connection();
            if (! $queue instanceof RedisQueue) {
                return [$queue->size(), null];
            }
            $created = $queue->creationTimeOfOldestPendingJob();

            return [(int) $queue->pendingSize(), $created === null ? null : max(0, time() - (int) $created)];
        }, [0, null]);
    }

    private function reachable(): bool
    {
        $socket = @fsockopen($this->realtimeHost, $this->realtimePort, $code, $message, self::SOCKET_TIMEOUT_SECONDS);
        if ($socket === false) {
            return false;
        }
        fclose($socket);

        return true;
    }

    /**
     * @template T
     * @template F
     *
     * @param  Closure(): T  $work
     * @param  F  $fallback
     * @return T|F
     */
    private function attempt(Closure $work, mixed $fallback): mixed
    {
        try {
            return $work();
        } catch (Throwable) {
            return $fallback;
        }
    }
}
