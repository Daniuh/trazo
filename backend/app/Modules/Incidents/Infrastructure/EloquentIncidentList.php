<?php

declare(strict_types=1);

// Listado de incidentes en SQL: visibilidad, filtros, búsqueda, orden y página, con las relaciones cargadas de una vez.
// Los índices que usa cada combinación están validados con EXPLAIN. Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Infrastructure;

use App\Modules\Incidents\Application\Data\IncidentListCriteria;
use App\Modules\Incidents\Application\Data\IncidentPage;
use App\Modules\Incidents\Application\Ports\IncidentListQuery;
use App\Modules\Incidents\Domain\VisibilityScope;
use App\Modules\Incidents\Infrastructure\Models\Incident;

final readonly class EloquentIncidentList implements IncidentListQuery
{
    public function __construct(
        private IncidentMapper $mapper,
        private IncidentFilters $filters,
    ) {}

    public function search(int $actorId, VisibilityScope $scope, IncidentListCriteria $criteria): IncidentPage
    {
        $query = $this->filters->query($actorId, $scope, $criteria);
        $total = (clone $query)->count();

        $items = $this->filters->sort($query, $criteria)
            ->with(['priority', 'status', 'creator:id,ulid,name', 'assignee:id,ulid,name'])
            ->forPage($criteria->page, $criteria->perPage)
            ->get();

        return new IncidentPage(
            array_values($items->map(fn (Incident $incident) => $this->mapper->toRecord($incident))->all()),
            $total,
            $criteria->page,
            $criteria->perPage,
        );
    }
}
