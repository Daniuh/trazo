<?php

declare(strict_types=1);

// Revisión de cada minuto (scheduler): deja la señal de vida del scheduler, pide la del worker y registra un evento
// por cada pieza que falla o por la cola acumulada. Una vez al día borra los eventos resueltos viejos.
// Documentado en docs/backend/system.md

namespace App\Modules\System\Application;

use App\Modules\Shared\Application\Ports\SystemEvents;
use App\Modules\Shared\Application\SystemEventType;
use App\Modules\System\Application\Ports\HealthProbe;
use App\Modules\System\Application\Ports\Heartbeats;
use App\Modules\System\Application\Ports\SystemEventLog;
use App\Modules\System\Domain\HealthRules;
use App\Modules\System\Domain\HealthStatus;
use DateTimeImmutable;

final readonly class CheckSystem
{
    public function __construct(
        private HealthProbe $probe,
        private Heartbeats $heartbeats,
        private SystemEvents $events,
        private SystemEventLog $log,
        private int $retentionDays,
    ) {}

    public function run(): void
    {
        $this->heartbeats->beat(Heartbeats::SCHEDULER);
        $this->heartbeats->requestWorkerBeat();
        $m = $this->probe->measure();

        if ($m->redisMs === null) {
            $this->down('redis', 'Redis no responde: caché, colas y límites de frecuencia quedan sin servicio');
        }
        if (! $m->realtimeReachable) {
            $this->down('realtime', 'El servidor de tiempo real no responde: las pantallas no reciben avisos');
        }
        // Sin señal todavía (recién arrancado) no es una falla; vieja sí
        if (HealthRules::heartbeat($m->workerSecondsAgo) === HealthStatus::Failing) {
            $this->down('worker', "El worker no procesa la cola desde hace {$m->workerSecondsAgo} segundos", ['seconds_ago' => $m->workerSecondsAgo]);
        }
        if (HealthRules::isBacklogged($m->pendingJobs, $m->oldestPendingSeconds)) {
            $this->events->record(SystemEventType::QueueBacklog, 'default', "La cola tiene {$m->pendingJobs} jobs pendientes", [
                'pending' => $m->pendingJobs,
                'oldest_seconds' => $m->oldestPendingSeconds,
            ]);
        }
    }

    public function prune(DateTimeImmutable $now): int
    {
        return $this->log->pruneResolvedBefore($now->modify("-{$this->retentionDays} days"));
    }

    /**
     * @param  array<string, scalar|null>  $context
     */
    private function down(string $service, string $message, array $context = []): void
    {
        $this->events->record(SystemEventType::ServiceDown, $service, $message, ['service' => $service, ...$context]);
    }
}
