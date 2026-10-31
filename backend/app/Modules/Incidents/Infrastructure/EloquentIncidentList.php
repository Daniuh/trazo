<?php

declare(strict_types=1);

// Listado de incidentes en SQL: visibilidad, filtros, búsqueda, orden y página, con las relaciones cargadas de una vez.
// Los índices que usa cada combinación están validados con EXPLAIN. Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Infrastructure;

use App\Modules\Incidents\Application\Data\IncidentListCriteria;
use App\Modules\Incidents\Application\Data\IncidentPage;
use App\Modules\Incidents\Application\Ports\IncidentCatalog;
use App\Modules\Incidents\Application\Ports\IncidentListQuery;
use App\Modules\Incidents\Domain\IncidentSort;
use App\Modules\Incidents\Domain\StatusFlags;
use App\Modules\Incidents\Domain\VisibilityScope;
use App\Modules\Incidents\Infrastructure\Models\Incident;
use Illuminate\Database\Eloquent\Builder;

final readonly class EloquentIncidentList implements IncidentListQuery
{
    public function __construct(
        private IncidentCatalog $catalog,
        private IncidentMapper $mapper,
        private IncidentFilters $filters,
    ) {}

    public function search(int $actorId, VisibilityScope $scope, IncidentListCriteria $criteria): IncidentPage
    {
        $query = Incident::query()->select('incidents.*');
        if ($criteria->deleted) {
            $query->onlyTrashed();
        }

        $this->visibility($query, $actorId, $scope);
        $this->filters->apply($query, $criteria, $this->pendingStatusIds());

        $total = (clone $query)->count();

        $items = $this->sorted($query, $criteria)
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

    /**
     * La misma regla del detalle (VisibilityScope), escrita como condición SQL.
     *
     * @param  Builder<Incident>  $query
     */
    private function visibility(Builder $query, int $actorId, VisibilityScope $scope): void
    {
        if ($scope->all) {
            return;
        }
        if ($scope->isEmpty()) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where(function (Builder $visible) use ($actorId, $scope): void {
            if ($scope->own) {
                $visible->orWhere('incidents.created_by', $actorId);
            }
            if ($scope->assigned) {
                $visible->orWhere('incidents.assigned_to', $actorId);
            }
            if ($scope->unassigned) {
                $visible->orWhereNull('incidents.assigned_to');
            }
        });
    }

    /**
     * Orden pedido y, siempre, el id como desempate: así las páginas no repiten ni saltan filas.
     *
     * @param  Builder<Incident>  $query
     * @return Builder<Incident>
     */
    private function sorted(Builder $query, IncidentListCriteria $criteria): Builder
    {
        $direction = $criteria->descending ? 'desc' : 'asc';

        match ($criteria->sort) {
            IncidentSort::Priority => $query->join('priorities', 'priorities.id', '=', 'incidents.priority_id')->orderBy('priorities.level', $direction),
            IncidentSort::Status => $query->join('statuses', 'statuses.id', '=', 'incidents.status_id')->orderBy('statuses.sort_order', $direction),
            IncidentSort::Code => null,
            default => $query->orderBy('incidents.'.$criteria->sort->value, $direction),
        };

        return $query->orderBy('incidents.id', $direction);
    }

    /**
     * @return list<int>
     */
    private function pendingStatusIds(): array
    {
        $pending = array_filter($this->catalog->statuses(), fn (StatusFlags $status): bool => $status->isPending);

        return array_values(array_map(fn (StatusFlags $status): int => $status->id, $pending));
    }
}
