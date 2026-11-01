<?php

declare(strict_types=1);

// Una página del listado: los incidentes, cuántos hay en total con esos filtros y, si se agrupa, cuántos tiene cada
// grupo de la página.
// Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Application\Data;

final readonly class IncidentPage
{
    /**
     * @param  list<IncidentRecord>  $items
     * @param  array<string, int>  $groups  Total de cada grupo que aparece en la página, por su clave
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $perPage,
        public array $groups = [],
    ) {}

    public function lastPage(): int
    {
        return max(1, (int) ceil($this->total / $this->perPage));
    }
}
