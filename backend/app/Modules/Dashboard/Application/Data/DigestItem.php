<?php

declare(strict_types=1);

// Lo mínimo de un incidente para mostrarlo en una lista del dashboard. Documentado en docs/backend/dashboard.md

namespace App\Modules\Dashboard\Application\Data;

use DateTimeImmutable;

final readonly class DigestItem
{
    public function __construct(
        public string $ulid,
        public string $code,
        public string $title,
        public int $priorityId,
        public int $statusId,
        public DateTimeImmutable $dueDate,
    ) {}
}
