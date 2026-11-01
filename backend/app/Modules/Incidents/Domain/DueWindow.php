<?php

declare(strict_types=1);

// Límites de los grupos de vencimiento para un momento dado: "hoy" y "los próximos 7 días" se cuentan en el día del
// negocio, no en UTC. Los límites quedan en UTC, como se guardan las fechas. Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Domain;

use DateTimeImmutable;
use DateTimeZone;

final readonly class DueWindow
{
    private const WEEK_DAYS = 7;

    public function __construct(
        public DateTimeImmutable $now,
        public DateTimeImmutable $endOfToday,
        public DateTimeImmutable $endOfWeek,
    ) {}

    public static function at(DateTimeImmutable $now, DateTimeZone $businessZone): self
    {
        $utc = new DateTimeZone('UTC');
        $now = $now->setTimezone($utc);
        $tomorrow = $now->setTimezone($businessZone)->setTime(0, 0)->modify('+1 day');

        return new self($now, $tomorrow->setTimezone($utc), $tomorrow->modify('+'.self::WEEK_DAYS.' days')->setTimezone($utc));
    }

    public function bucket(StatusFlags $status, DateTimeImmutable $dueDate): DueBucket
    {
        return match (true) {
            ! $status->isPending => DueBucket::Finished,
            $dueDate < $this->now => DueBucket::Overdue,
            $dueDate < $this->endOfToday => DueBucket::Today,
            $dueDate < $this->endOfWeek => DueBucket::Week,
            default => DueBucket::Later,
        };
    }
}
