<?php

declare(strict_types=1);

// Acciones sobre la propia app, sin acceso a Docker. Reiniciar usa las señales de Laravel: workers y Reverb terminan
// limpios (el worker después del job en curso) y Docker los vuelve a levantar. La caché que se limpia es solo la de
// las cifras del dashboard: los bloqueos, límites y sesiones siguen intactos. Documentado en docs/backend/system.md

namespace App\Modules\System\Infrastructure;

use App\Modules\Dashboard\Application\Ports\MetricsCache;
use App\Modules\System\Application\Ports\SystemControls;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Artisan;

final readonly class LaravelSystemControls implements SystemControls
{
    // La misma señal que deja "artisan reverb:restart": el servidor la revisa cada pocos segundos y se cierra. Se
    // escribe directo porque Reverb solo registra sus comandos en la consola, no en una petición web
    private const REVERB_RESTART_KEY = 'laravel:reverb:restart';

    public function __construct(
        private MetricsCache $metrics,
        private Repository $cache,
    ) {}

    public function restartWorkers(): void
    {
        Artisan::call('queue:restart');
    }

    public function restartRealtime(): void
    {
        $this->cache->forever(self::REVERB_RESTART_KEY, now()->getTimestamp());
    }

    public function clearDataCache(): void
    {
        $this->metrics->invalidate();
    }
}
