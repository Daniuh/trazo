<?php

declare(strict_types=1);

// Un estado del catálogo y lo que significa para el ciclo de vida (inicial, pendiente, final).
// Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Domain;

final readonly class StatusFlags
{
    public function __construct(
        public int $id,
        public string $code,
        public string $name,
        public bool $isInitial,
        public bool $isPending,
        public bool $isFinal,
    ) {}
}
