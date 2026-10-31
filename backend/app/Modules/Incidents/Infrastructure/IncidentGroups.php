<?php

declare(strict_types=1);

// Grupos del listado en SQL (ADR-0080). El listado no ordena todo por grupo: cuenta cada grupo en orden, ve en cuáles cae
// la página y pide solo esos, cada uno con un filtro que usa los índices. La exportación sí ordena todo por grupo, porque
// lee todas las filas igual. Las claves son las que ve el front en cada incidente: grupo de vencimiento, código de estado
// o prioridad, o ULID del asignado ("none" si no tiene). Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Infrastructure;

use App\Modules\Incidents\Application\DueWindows;
use App\Modules\Incidents\Domain\DueBucket;
use App\Modules\Incidents\Domain\IncidentGroup;
use App\Modules\Incidents\Infrastructure\Models\Incident;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use stdClass;

final readonly class IncidentGroups
{
    public const string UNASSIGNED = 'none';

    public function __construct(
        private IncidentFilters $filters,
        private DueWindows $windows,
    ) {}

    /**
     * Cada grupo que tiene incidentes, en el orden en que se muestra, con su total.
     *
     * @param  Builder<Incident>  $query  Lo filtrado, sin orden ni página
     * @return list<IncidentGroupSlice>
     */
    public function totals(Builder $query, IncidentGroup $group): array
    {
        // Solo la columna del grupo y el conteo, sin cruzar tablas: MySQL lo resuelve con un índice. El orden de los
        // pocos grupos que salen se pone después
        $base = (clone $query)->toBase()->select([]);
        match ($group) {
            IncidentGroup::Due => $base->selectRaw(...$this->dueKey()),
            IncidentGroup::Status => $base->selectRaw('incidents.status_id as group_key'),
            // Por estado y prioridad: así lo cubre el índice (status_id, priority_id, deleted_at…); se suma después
            IncidentGroup::Priority => $base->selectRaw('incidents.priority_id as group_key')->groupBy('incidents.status_id'),
            IncidentGroup::Assignee => $base->selectRaw('incidents.assigned_to as group_key'),
        };

        // "Sin asignar" llega como null: se guarda con la clave vacía
        $counts = [];
        foreach ($base->selectRaw('count(*) as total')->groupBy('group_key')->get() as $row) {
            $key = $row->group_key === null ? '' : (int) $row->group_key;
            $counts[$key] = ($counts[$key] ?? 0) + (int) $row->total;
        }

        $slices = [];
        foreach ($this->ordered($group, $counts) as [$value, $key]) {
            $slices[] = new IncidentGroupSlice($key, $group === IncidentGroup::Due ? null : $value, $counts[$value ?? '']);
        }

        return $slices;
    }

    /**
     * Solo los incidentes de un grupo. Cada filtro usa los mismos índices que el resto del listado.
     *
     * @param  Builder<Incident>  $query
     * @return Builder<Incident>
     */
    public function restrict(Builder $query, IncidentGroup $group, IncidentGroupSlice $slice): Builder
    {
        return match ($group) {
            IncidentGroup::Due => $this->inBucket($query, DueBucket::from($slice->key)),
            IncidentGroup::Status => $query->where('incidents.status_id', $slice->value),
            IncidentGroup::Priority => $query->where('incidents.priority_id', $slice->value),
            IncidentGroup::Assignee => $slice->value === null
                ? $query->whereNull('incidents.assigned_to')
                : $query->where('incidents.assigned_to', $slice->value),
        };
    }

    /**
     * Orden completo por grupo, antes del orden elegido. Lo usa la exportación, que lee todas las filas.
     * Los joins llevan alias propios para convivir con los del orden (estado y prioridad).
     *
     * @param  Builder<Incident>  $query
     * @return Builder<Incident>
     */
    public function order(Builder $query, ?IncidentGroup $group): Builder
    {
        match ($group) {
            IncidentGroup::Due => $query->orderByRaw(...$this->dueBucketSql()),
            IncidentGroup::Status => $query->join('statuses as group_status', 'group_status.id', '=', 'incidents.status_id')
                ->orderBy('group_status.sort_order'),
            IncidentGroup::Priority => $query->join('priorities as group_priority', 'group_priority.id', '=', 'incidents.priority_id')
                ->orderByDesc('group_priority.level'),
            IncidentGroup::Assignee => $query->leftJoin('users as group_assignee', 'group_assignee.id', '=', 'incidents.assigned_to')
                ->orderByRaw('incidents.assigned_to IS NOT NULL')->orderBy('group_assignee.name')->orderBy('incidents.assigned_to'),
            null => null,
        };

        return $query;
    }

    /**
     * El grupo de vencimiento en SQL, con la misma regla de DueWindow: devuelve la posición del grupo en DueBucket
     * (0 = vencido, 1 = hoy, 2 = semana, 3 = más adelante, 4 = terminado).
     *
     * @return array{0: literal-string, 1: list<mixed>}
     */
    private function dueBucketSql(): array
    {
        $window = $this->windows->current();
        $sql = 'CASE WHEN incidents.status_id NOT IN (SELECT id FROM statuses WHERE is_pending = 1) THEN 4 '
            .'WHEN incidents.due_date < ? THEN 0 WHEN incidents.due_date < ? THEN 1 WHEN incidents.due_date < ? THEN 2 ELSE 3 END';

        return [$sql, [$window->now, $window->endOfToday, $window->endOfWeek]];
    }

    /**
     * @return array{0: literal-string, 1: list<mixed>}
     */
    private function dueKey(): array
    {
        [$sql, $bindings] = $this->dueBucketSql();

        return [$sql.' as group_key', $bindings];
    }

    /**
     * Un grupo de vencimiento como rango: pendientes con el vencimiento entre dos límites, o terminados.
     *
     * @param  Builder<Incident>  $query
     * @return Builder<Incident>
     */
    private function inBucket(Builder $query, DueBucket $bucket): Builder
    {
        $window = $this->windows->current();
        $pending = $this->filters->pendingStatusIds();
        if ($bucket === DueBucket::Finished) {
            return $query->whereNotIn('incidents.status_id', $pending);
        }

        [$from, $to] = match ($bucket) {
            DueBucket::Overdue => [null, $window->now],
            DueBucket::Today => [$window->now, $window->endOfToday],
            DueBucket::Week => [$window->endOfToday, $window->endOfWeek],
            DueBucket::Later => [$window->endOfWeek, null],
        };
        $query->whereIn('incidents.status_id', $pending);
        if ($from !== null) {
            $query->where('incidents.due_date', '>=', $from);
        }
        if ($to !== null) {
            $query->where('incidents.due_date', '<', $to);
        }

        return $query;
    }

    /**
     * Los grupos que tienen incidentes, en el orden en que se muestran, como [valor en la base, clave pública].
     * Vencimiento: en el orden de DueBucket; estado: el del flujo; prioridad: de la más alta a la más baja; asignado:
     * primero "Sin asignar" y luego por nombre (el id separa a dos personas con el mismo nombre). Se leen también los
     * estados y prioridades inactivos, para que ningún incidente se quede sin grupo.
     *
     * @param  array<int|string, int>  $counts
     * @return list<array{0: int|null, 1: string}>
     */
    private function ordered(IncidentGroup $group, array $counts): array
    {
        $ids = array_values(array_filter(array_keys($counts), 'is_int'));
        $pairs = fn (Collection $rows, string $key): array => $rows
            ->map(fn (stdClass $row): array => [(int) $row->id, (string) $row->{$key}])->values()->all();

        return array_values(match ($group) {
            IncidentGroup::Due => array_filter(
                array_map(fn (DueBucket $bucket, int $position): array => [$position, $bucket->value], DueBucket::cases(), array_keys(DueBucket::cases())),
                fn (array $pair): bool => isset($counts[$pair[0]]),
            ),
            IncidentGroup::Status => $pairs(DB::table('statuses')->whereIn('id', $ids)->orderBy('sort_order')->orderBy('id')->get(['id', 'code']), 'code'),
            IncidentGroup::Priority => $pairs(DB::table('priorities')->whereIn('id', $ids)->orderByDesc('level')->orderBy('id')->get(['id', 'code']), 'code'),
            IncidentGroup::Assignee => [
                ...(isset($counts['']) ? [[null, self::UNASSIGNED]] : []),
                ...$pairs(DB::table('users')->whereIn('id', $ids)->orderBy('name')->orderBy('id')->get(['id', 'ulid']), 'ulid'),
            ],
        });
    }
}
