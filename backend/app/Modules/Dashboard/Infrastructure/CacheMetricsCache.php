<?php

declare(strict_types=1);

// Caché de métricas sobre la caché de Laravel (Redis en Docker). Las claves llevan un número de generación:
// invalidar es subir ese número, sin buscar ni borrar claves. Lo viejo expira solo. Documentado en docs/backend/dashboard.md

namespace App\Modules\Dashboard\Infrastructure;

use App\Modules\Dashboard\Application\Data\CachedMetric;
use App\Modules\Dashboard\Application\Ports\MetricsCache;
use Closure;
use DateTimeImmutable;
use Illuminate\Contracts\Cache\Repository;

final readonly class CacheMetricsCache implements MetricsCache
{
    private const GENERATION_KEY = 'dashboard:generation';

    public function __construct(
        private Repository $cache,
        private int $ttlSeconds,
    ) {}

    public function remember(string $key, Closure $compute): CachedMetric
    {
        $generation = (int) $this->cache->get(self::GENERATION_KEY, 0);

        /** @var array{at: string, value: mixed} $entry */
        $entry = $this->cache->remember(
            "dashboard:{$generation}:{$key}",
            $this->ttlSeconds,
            fn (): array => ['at' => now()->toIso8601String(), 'value' => $compute()],
        );

        return new CachedMetric($entry['value'], new DateTimeImmutable($entry['at']));
    }

    public function invalidate(): void
    {
        // add no pisa un valor existente; increment es atómico en Redis
        $this->cache->add(self::GENERATION_KEY, 0);
        $this->cache->increment(self::GENERATION_KEY);
    }
}
