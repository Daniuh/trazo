<?php

declare(strict_types=1);

// Qué pidió la persona en el listado: página, orden, búsqueda y filtros, ya validados.
// Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Application\Data;

use App\Modules\Incidents\Domain\IncidentGroup;
use App\Modules\Incidents\Domain\IncidentSort;
use DateTimeImmutable;

final readonly class IncidentListCriteria
{
    /**
     * @param  list<string>  $statuses  Códigos de estado
     * @param  list<string>  $priorities  Códigos de prioridad
     * @param  list<string>  $assignees  ULIDs de personas
     */
    public function __construct(
        public int $page = 1,
        public int $perPage = 20,
        public IncidentSort $sort = IncidentSort::CreatedAt,
        public bool $descending = true,
        public ?string $search = null,
        public array $statuses = [],
        public array $priorities = [],
        public array $assignees = [],
        // "Sin asignar" se combina con las personas elegidas
        public bool $unassigned = false,
        public ?string $creator = null,
        public ?DateTimeImmutable $createdFrom = null,
        public ?DateTimeImmutable $createdTo = null,
        public ?DateTimeImmutable $dueFrom = null,
        public ?DateTimeImmutable $dueTo = null,
        public bool $overdue = false,
        public bool $deleted = false,
        // Solo los que tienen una solicitud de reapertura pendiente
        public bool $reopenRequested = false,
        // Sin grupo, solo el orden elegido; con grupo, primero el grupo y dentro de él ese orden
        public ?IncidentGroup $group = null,
    ) {}
}
