<?php

declare(strict_types=1);

// Rango de fechas de la auditoría. Sin fechas, los últimos 30 días; con solo el final, los 30 días anteriores a él;
// con solo el inicio, hasta ahora. Así ninguna consulta recorre toda la tabla sin querer.
// Documentado en docs/backend/audit.md

namespace App\Modules\Audit\Domain;

use DateTimeImmutable;

final readonly class AuditPeriod
{
    public const DEFAULT_DAYS = 30;

    private function __construct(
        public DateTimeImmutable $from,
        public DateTimeImmutable $to,
    ) {}

    public static function between(?DateTimeImmutable $from, ?DateTimeImmutable $to, DateTimeImmutable $now): self
    {
        $to ??= $now;
        $from ??= $to->modify('-'.self::DEFAULT_DAYS.' days');

        return new self($from, $to);
    }
}
