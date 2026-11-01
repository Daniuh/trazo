<?php

declare(strict_types=1);

// Caché de las métricas del dashboard. Cada cambio en un incidente la invalida completa (invalidate),
// así una cifra nunca contradice lo que ya se guardó. Documentado en docs/backend/dashboard.md

namespace App\Modules\Dashboard\Application\Ports;

use App\Modules\Dashboard\Application\Data\CachedMetric;
use Closure;

interface MetricsCache
{
    /**
     * @param  Closure(): mixed  $compute
     */
    public function remember(string $key, Closure $compute): CachedMetric;

    public function invalidate(): void;
}
