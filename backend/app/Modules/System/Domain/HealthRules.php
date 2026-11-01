<?php

declare(strict_types=1);

// Umbrales del panel de salud, en un solo lugar: cuándo una medición pasa a "atención" o a "falla". Los usan el
// panel y la revisión de cada minuto, que registra un evento cuando algo falla. Documentado en docs/backend/system.md

namespace App\Modules\System\Domain;

final class HealthRules
{
    // Respuesta de la base de datos o de Redis
    public const SLOW_MS = 200;

    // Señal de vida de workers y scheduler: se deja cada minuto
    public const HEARTBEAT_STALE_SECONDS = 120;

    // Cola acumulada
    public const QUEUE_PENDING_LIMIT = 100;

    public const QUEUE_OLDEST_SECONDS = 300;

    // Espacio libre del almacenamiento
    public const DISK_WARNING_RATIO = 0.10;

    public const DISK_FAILING_RATIO = 0.05;

    // null: no respondió
    public static function latency(?float $milliseconds): HealthStatus
    {
        return match (true) {
            $milliseconds === null => HealthStatus::Failing,
            $milliseconds > self::SLOW_MS => HealthStatus::Warning,
            default => HealthStatus::Ok,
        };
    }

    // null: todavía no hay señal (recién arrancado); se avisa sin darlo por caído
    public static function heartbeat(?int $secondsAgo): HealthStatus
    {
        return match (true) {
            $secondsAgo === null => HealthStatus::Warning,
            $secondsAgo > self::HEARTBEAT_STALE_SECONDS => HealthStatus::Failing,
            default => HealthStatus::Ok,
        };
    }

    public static function queue(int $pending, ?int $oldestSeconds, int $failed): HealthStatus
    {
        return self::isBacklogged($pending, $oldestSeconds) || $failed > 0 ? HealthStatus::Warning : HealthStatus::Ok;
    }

    public static function isBacklogged(int $pending, ?int $oldestSeconds): bool
    {
        return $pending > self::QUEUE_PENDING_LIMIT || ($oldestSeconds ?? 0) > self::QUEUE_OLDEST_SECONDS;
    }

    public static function disk(int $freeBytes, int $totalBytes): HealthStatus
    {
        $ratio = $totalBytes > 0 ? $freeBytes / $totalBytes : 0.0;

        return match (true) {
            $ratio < self::DISK_FAILING_RATIO => HealthStatus::Failing,
            $ratio < self::DISK_WARNING_RATIO => HealthStatus::Warning,
            default => HealthStatus::Ok,
        };
    }

    public static function reachable(bool $reachable): HealthStatus
    {
        return $reachable ? HealthStatus::Ok : HealthStatus::Failing;
    }
}
