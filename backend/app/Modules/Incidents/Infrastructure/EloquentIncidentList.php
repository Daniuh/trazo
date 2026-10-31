<?php

declare(strict_types=1);

// Listado de incidentes en SQL: visibilidad, filtros, búsqueda, orden y página, con las relaciones cargadas de una vez.
// Agrupado, no ordena todo por grupo: cuenta cada grupo, ve en cuáles cae la página y pide solo esos (ADR-0080).
// Los índices que usa cada combinación están validados con EXPLAIN. Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Infrastructure;

use App\Modules\Incidents\Application\Data\IncidentListCriteria;
use App\Modules\Incidents\Application\Data\IncidentPage;
use App\Modules\Incidents\Application\Data\IncidentRecord;
use App\Modules\Incidents\Application\Ports\IncidentListQuery;
use App\Modules\Incidents\Domain\IncidentGroup;
use App\Modules\Incidents\Domain\VisibilityScope;
use App\Modules\Incidents\Infrastructure\Models\Incident;
use Illuminate\Database\Eloquent\Builder;

final readonly class EloquentIncidentList implements IncidentListQuery
{
    public function __construct(
        private IncidentMapper $mapper,
        private IncidentFilters $filters,
        private IncidentGroups $groups,
    ) {}

    public function search(int $actorId, VisibilityScope $scope, IncidentListCriteria $criteria): IncidentPage
    {
        $query = $this->filters->query($actorId, $scope, $criteria);
        if ($criteria->group !== null) {
            return $this->grouped($query, $criteria, $criteria->group);
        }

        $total = (clone $query)->count();
        $models = $this->filters->sort($query, $criteria)
            ->with(IncidentMapper::RELATIONS)
            ->forPage($criteria->page, $criteria->perPage)
            ->get();

        return new IncidentPage($this->records($models->all()), $total, $criteria->page, $criteria->perPage);
    }

    /**
     * Recorre los grupos en orden saltando los que quedan antes de la página; de cada grupo que toca la página pide
     * solo los ids que caen en ella. Al final carga esos incidentes de una vez, en el mismo orden.
     *
     * @param  Builder<Incident>  $query
     */
    private function grouped(Builder $query, IncidentListCriteria $criteria, IncidentGroup $group): IncidentPage
    {
        $slices = $this->groups->totals($query, $group);
        $skip = ($criteria->page - 1) * $criteria->perPage;
        $missing = $criteria->perPage;
        $ids = [];
        $totals = [];

        foreach ($slices as $slice) {
            if ($missing === 0) {
                break;
            }
            if ($skip >= $slice->total) {
                $skip -= $slice->total;

                continue;
            }

            $found = $this->filters->sort($this->groups->restrict(clone $query, $group, $slice), $criteria)
                ->offset($skip)->limit($missing)->pluck('incidents.id')->all();
            $ids = [...$ids, ...array_map('intval', $found)];
            $totals[$slice->key] = $slice->total;
            $missing -= count($found);
            $skip = 0;
        }

        // Con trashed: la vista de eliminados ya filtró por su cuenta; aquí solo se cargan los ids elegidos
        $models = $ids === [] ? [] : Incident::withTrashed()->with(IncidentMapper::RELATIONS)
            ->whereIn('id', $ids)->get()->keyBy('id')->all();
        $ordered = array_values(array_filter(array_map(fn (int $id): ?Incident => $models[$id] ?? null, $ids)));
        $total = array_sum(array_map(fn (IncidentGroupSlice $slice): int => $slice->total, $slices));

        return new IncidentPage($this->records($ordered), $total, $criteria->page, $criteria->perPage, $totals);
    }

    /**
     * @param  array<int, Incident>  $models
     * @return list<IncidentRecord>
     */
    private function records(array $models): array
    {
        return array_values(array_map(fn (Incident $incident): IncidentRecord => $this->mapper->toRecord($incident), $models));
    }
}
