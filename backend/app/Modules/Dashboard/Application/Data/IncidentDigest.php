<?php

declare(strict_types=1);

// Lista corta de incidentes para un widget, con el total que hay detrás de ella. Documentado en docs/backend/dashboard.md

namespace App\Modules\Dashboard\Application\Data;

final readonly class IncidentDigest
{
    /**
     * @param  list<DigestItem>  $items
     */
    public function __construct(
        public int $total,
        public array $items,
    ) {}
}
