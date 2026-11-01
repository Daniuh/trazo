<?php

declare(strict_types=1);

// Agregados del dashboard en SQL, siempre sobre incidentes no eliminados y con la visibilidad del listado (VisibilitySql).
// Los índices que usa cada consulta están validados con EXPLAIN. Documentado en docs/base-de-datos/rendimiento.md

namespace App\Modules\Dashboard\Infrastructure;

use App\Modules\Dashboard\Application\Data\DigestItem;
use App\Modules\Dashboard\Application\Data\IncidentDigest;
use App\Modules\Dashboard\Application\Data\WorkloadRow;
use App\Modules\Dashboard\Application\Ports\DashboardQueries;
use App\Modules\Incidents\Domain\DueWindow;
use App\Modules\Incidents\Domain\VisibilityScope;
use App\Modules\Incidents\Infrastructure\Models\Incident;
use App\Modules\Incidents\Infrastructure\VisibilitySql;
use App\Modules\Users\Infrastructure\Models\User;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Builder;

final class EloquentDashboardQueries implements DashboardQueries
{
    public function countsByStatusAndPriority(int $actorId, VisibilityScope $scope): array
    {
        $rows = $this->visible($actorId, $scope)->toBase()
            ->select(['status_id', 'priority_id'])
            ->selectRaw('COUNT(*) AS total')
            ->groupBy(['status_id', 'priority_id'])
            ->get();

        $counts = [];
        foreach ($rows as $row) {
            $counts[(int) $row->status_id][(int) $row->priority_id] = (int) $row->total;
        }

        return $counts;
    }

    public function overdueCount(int $actorId, VisibilityScope $scope, array $pendingStatusIds, DateTimeImmutable $now): int
    {
        return $this->visible($actorId, $scope)
            ->whereIn('incidents.status_id', $pendingStatusIds)
            ->where('incidents.due_date', '<', $now)
            ->count();
    }

    public function unassignedCount(int $actorId, VisibilityScope $scope, array $pendingStatusIds): int
    {
        return $this->visible($actorId, $scope)
            ->whereIn('incidents.status_id', $pendingStatusIds)
            ->whereNull('incidents.assigned_to')
            ->count();
    }

    public function countByHour(int $actorId, VisibilityScope $scope, string $column, DateTimeImmutable $from): array
    {
        // Por hora y no por día: el día se arma después en la zona horaria del negocio
        return $this->visible($actorId, $scope)->toBase()
            ->selectRaw("DATE_FORMAT(incidents.{$column}, '%Y-%m-%d %H:00:00') AS hour")
            ->selectRaw('COUNT(*) AS total')
            ->where("incidents.{$column}", '>=', $from)
            ->groupBy('hour')
            ->pluck('total', 'hour')
            ->map(fn (mixed $total): int => (int) $total)
            ->all();
    }

    public function dueBetween(int $actorId, VisibilityScope $scope, array $pendingStatusIds, DateTimeImmutable $from, DateTimeImmutable $to, int $limit): IncidentDigest
    {
        $query = $this->visible($actorId, $scope)
            ->whereIn('incidents.status_id', $pendingStatusIds)
            ->whereBetween('incidents.due_date', [$from, $to]);

        return $this->digest($query, $limit);
    }

    public function assignedTo(int $actorId, VisibilityScope $scope, array $pendingStatusIds, int $limit): IncidentDigest
    {
        // Sin la indicación, MySQL recorre todo el índice de vencimientos (incluidos los cerrados) para evitar ordenar;
        // ordenar los pendientes de una persona es mucho más barato. Medido en docs/base-de-datos/rendimiento.md
        $query = $this->visible($actorId, $scope)
            ->useIndex('incidents_dashboard_assignee_index')
            ->whereIn('incidents.status_id', $pendingStatusIds)
            ->where('incidents.assigned_to', $actorId);

        return $this->digest($query, $limit);
    }

    public function workload(int $actorId, VisibilityScope $scope, array $pendingStatusIds, DateTimeImmutable $now, int $limit): array
    {
        $rows = $this->visible($actorId, $scope)->toBase()
            ->select('incidents.assigned_to')
            ->selectRaw('COUNT(*) AS pending')
            ->selectRaw('SUM(incidents.due_date < ?) AS overdue', [$now])
            ->whereIn('incidents.status_id', $pendingStatusIds)
            ->whereNotNull('incidents.assigned_to')
            ->groupBy('incidents.assigned_to')
            ->orderByDesc('pending')
            ->orderBy('incidents.assigned_to')
            ->limit($limit)
            ->get();

        // Los nombres, en una sola consulta para todas las filas
        $people = User::query()->whereKey($rows->pluck('assigned_to')->all())->get(['id', 'ulid', 'name'])->keyBy('id');

        return array_values($rows
            ->filter(fn (object $row): bool => $people->has((int) $row->assigned_to))
            ->map(function (object $row) use ($people): WorkloadRow {
                $person = $people->get((int) $row->assigned_to);

                return new WorkloadRow((string) $person?->ulid, (string) $person?->name, (int) $row->pending, (int) $row->overdue);
            })
            ->all());
    }

    public function onTimeResolutions(int $actorId, VisibilityScope $scope, DateTimeImmutable $from): array
    {
        $row = $this->visible($actorId, $scope)->toBase()
            ->selectRaw('COUNT(*) AS resolved')
            ->selectRaw('COALESCE(SUM(incidents.resolved_at <= incidents.due_date), 0) AS on_time')
            ->where('incidents.resolved_at', '>=', $from)
            ->first();

        return ['resolved' => (int) ($row->resolved ?? 0), 'on_time' => (int) ($row->on_time ?? 0)];
    }

    public function riskMatrix(int $actorId, VisibilityScope $scope, array $pendingStatusIds, DueWindow $window): array
    {
        // Mismos límites que los grupos de la lista de incidentes (DueWindow)
        $rows = $this->visible($actorId, $scope)->toBase()
            ->select('incidents.priority_id')
            ->selectRaw('CASE WHEN incidents.due_date < ? THEN 0 WHEN incidents.due_date < ? THEN 1 WHEN incidents.due_date < ? THEN 2 ELSE 3 END AS bucket', [$window->now, $window->endOfToday, $window->endOfWeek])
            ->selectRaw('COUNT(*) AS total')
            ->whereIn('incidents.status_id', $pendingStatusIds)
            ->groupBy('incidents.priority_id', 'bucket')
            ->get();

        $matrix = [];
        foreach ($rows as $row) {
            $matrix[(int) $row->priority_id][(int) $row->bucket] = (int) $row->total;
        }

        return $matrix;
    }

    public function exitsWithoutResolution(int $actorId, VisibilityScope $scope, array $pendingStatusIds, DateTimeImmutable $from): array
    {
        return $this->visible($actorId, $scope)->toBase()
            ->selectRaw("DATE_FORMAT(incidents.updated_at, '%Y-%m-%d %H:00:00') AS hour")
            ->selectRaw('COUNT(*) AS total')
            ->whereNotIn('incidents.status_id', $pendingStatusIds)
            ->whereNull('incidents.resolved_at')
            ->where('incidents.updated_at', '>=', $from)
            ->groupBy('hour')
            ->pluck('total', 'hour')
            ->map(fn (mixed $total): int => (int) $total)
            ->all();
    }

    public function userUlid(int $userId): string
    {
        return (string) User::query()->whereKey($userId)->value('ulid');
    }

    /**
     * @return Builder<Incident>
     */
    private function visible(int $actorId, VisibilityScope $scope): Builder
    {
        $query = Incident::query();
        VisibilitySql::apply($query, $actorId, $scope);

        return $query;
    }

    /**
     * @param  Builder<Incident>  $query
     */
    private function digest(Builder $query, int $limit): IncidentDigest
    {
        $total = (clone $query)->count();
        $items = $query
            ->orderBy('incidents.due_date')
            ->orderBy('incidents.id')
            ->limit($limit)
            ->get(['incidents.ulid', 'incidents.code', 'incidents.title', 'incidents.priority_id', 'incidents.status_id', 'incidents.due_date']);

        return new IncidentDigest($total, array_values($items->map(fn (Incident $incident): DigestItem => new DigestItem(
            $incident->ulid,
            $incident->code,
            $incident->title,
            $incident->priority_id,
            $incident->status_id,
            $incident->due_date->toDateTimeImmutable(),
        ))->all()));
    }
}
