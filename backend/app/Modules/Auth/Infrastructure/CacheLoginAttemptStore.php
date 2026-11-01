<?php

declare(strict_types=1);

// Intentos fallidos y bloqueos en la caché (Redis). Se pierden si Redis se reinicia, lo que solo acorta una espera.
// Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Infrastructure;

use App\Modules\Auth\Domain\LoginAttemptStore;
use Illuminate\Contracts\Cache\Repository;

final readonly class CacheLoginAttemptStore implements LoginAttemptStore
{
    public function __construct(
        private Repository $cache,
        private int $failuresTtlSeconds,
    ) {}

    public function lockedFor(string $key): int
    {
        $until = $this->cache->get($this->lockKey($key));

        return is_int($until) ? max(0, $until - now()->getTimestamp()) : 0;
    }

    public function recordFailure(string $key): int
    {
        $failuresKey = $this->failuresKey($key);
        // add() solo crea la clave si no existe, con su vencimiento; increment() es atómico
        $this->cache->add($failuresKey, 0, $this->failuresTtlSeconds);

        return (int) $this->cache->increment($failuresKey);
    }

    public function lock(string $key, int $seconds): void
    {
        $this->cache->put($this->lockKey($key), now()->getTimestamp() + $seconds, $seconds);
    }

    public function clear(string $key): void
    {
        $this->cache->forget($this->failuresKey($key));
        $this->cache->forget($this->lockKey($key));
    }

    private function failuresKey(string $key): string
    {
        return "auth:failures:{$key}";
    }

    private function lockKey(string $key): string
    {
        return "auth:lock:{$key}";
    }
}
