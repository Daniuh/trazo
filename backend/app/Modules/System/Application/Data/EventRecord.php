<?php

declare(strict_types=1);

// Un evento del sistema tal como lo ve el Super admin: tipo, severidad, mensaje, contexto, cuántas veces y cuándo,
// estado y quién lo cambió por última vez. Documentado en docs/backend/system.md

namespace App\Modules\System\Application\Data;

use App\Modules\System\Domain\EventStatus;
use DateTimeImmutable;

final readonly class EventRecord
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public int $id,
        public EventType $type,
        public string $message,
        public array $context,
        public int $occurrences,
        public DateTimeImmutable $firstSeenAt,
        public DateTimeImmutable $lastSeenAt,
        public ?string $traceId,
        public EventStatus $status,
        public ?string $changedByName,
        public ?DateTimeImmutable $changedAt,
    ) {}
}
