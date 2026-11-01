<?php

declare(strict_types=1);

// Señales de vida de los procesos largos. El scheduler deja la suya cada minuto y encola un job que deja la del
// worker: si esa señal envejece, el worker no está procesando la cola. Documentado en docs/backend/system.md

namespace App\Modules\System\Application\Ports;

interface Heartbeats
{
    public const WORKER = 'worker';

    public const SCHEDULER = 'scheduler';

    public function beat(string $process): void;

    // Pide al worker que deje su señal (por la cola, así se comprueba que la procesa)
    public function requestWorkerBeat(): void;

    // Segundos desde la última señal; null si nunca dejó una
    public function secondsAgo(string $process): ?int;
}
