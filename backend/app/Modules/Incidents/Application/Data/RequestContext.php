<?php

declare(strict_types=1);

// Desde dónde se hizo un cambio. Se guarda en el historial para la auditoría.
// Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Application\Data;

final readonly class RequestContext
{
    public function __construct(
        public ?string $ip,
        public ?string $userAgent,
    ) {}
}
