<?php

declare(strict_types=1);

// Acciones sobre la propia app, sin acceso a Docker. Reiniciar usa las señales de Laravel: workers y Reverb terminan
// limpios (el worker después del job en curso) y Docker los vuelve a levantar. La caché que se limpia es solo la de
// las cifras del dashboard: los bloqueos, límites y sesiones siguen intactos. Documentado en docs/backend/system.md

namespace App\Modules\System\Infrastructure;

use App\Modules\Dashboard\Application\Ports\MetricsCache;
use App\Modules\System\Application\Ports\SystemControls;
use Illuminate\Support\Facades\Artisan;

final readonly class LaravelSystemControls implements SystemControls
{
    public function __construct(private MetricsCache $metrics) {}

    public function restartWorkers(): void
    {
        Artisan::call('queue:restart');
    }

    public function restartRealtime(): void
    {
        Artisan::call('reverb:restart');
    }

    public function clearDataCache(): void
    {
        $this->metrics->invalidate();
    }
}
