<?php

declare(strict_types=1);

// Cuando un incidente cambia, la caché del dashboard se descarta: la siguiente consulta trae cifras al día.
// Documentado en docs/backend/dashboard.md

namespace App\Modules\Dashboard\Infrastructure;

use App\Modules\Dashboard\Application\Ports\MetricsCache;
use App\Modules\Incidents\Application\Events\IncidentChanged;

final readonly class InvalidateDashboardMetrics
{
    public function __construct(private MetricsCache $cache) {}

    public function handle(IncidentChanged $event): void
    {
        $this->cache->invalidate();
    }
}
