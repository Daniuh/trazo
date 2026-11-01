<?php

declare(strict_types=1);

// Un valor de la caché y el momento en que se calculó (para "Actualizado hace…"). Documentado en docs/backend/dashboard.md

namespace App\Modules\Dashboard\Application\Data;

use DateTimeImmutable;

final readonly class CachedMetric
{
    public function __construct(
        public mixed $value,
        public DateTimeImmutable $generatedAt,
    ) {}
}
