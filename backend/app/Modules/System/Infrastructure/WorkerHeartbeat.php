<?php

declare(strict_types=1);

// Job mínimo que encola el scheduler cada minuto: al procesarlo, el worker deja su señal de vida. Un solo intento:
// si falla, el siguiente minuto encola otro. Documentado en docs/backend/system.md

namespace App\Modules\System\Infrastructure;

use App\Modules\System\Application\Ports\Heartbeats;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class WorkerHeartbeat implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function handle(Heartbeats $heartbeats): void
    {
        $heartbeats->beat(Heartbeats::WORKER);
    }
}
