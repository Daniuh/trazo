<?php

declare(strict_types=1);

// Desde dónde se hizo un cambio. Se guarda en los registros de auditoría.
// Documentado en docs/backend/shared.md

namespace App\Modules\Shared\Application\Data;

final readonly class RequestContext
{
    public function __construct(
        public ?string $ip,
        public ?string $userAgent,
    ) {}
}
