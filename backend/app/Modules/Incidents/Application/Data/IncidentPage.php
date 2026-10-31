<?php

declare(strict_types=1);

// Una página del listado: los incidentes y cuántos hay en total con esos filtros.
// Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Application\Data;

final readonly class IncidentPage
{
    /**
     * @param  list<IncidentRecord>  $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $perPage,
    ) {}

    public function lastPage(): int
    {
        return max(1, (int) ceil($this->total / $this->perPage));
    }
}
