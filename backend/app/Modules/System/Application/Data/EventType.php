<?php

declare(strict_types=1);

// Un tipo del catálogo de eventos, con su categoría y severidad. Documentado en docs/backend/system.md

namespace App\Modules\System\Application\Data;

final readonly class EventType
{
    public function __construct(
        public string $code,
        public string $name,
        public string $category,
        public string $severity,
    ) {}
}
