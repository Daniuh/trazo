<?php

declare(strict_types=1);

// "Mis incidentes" en SQL: asignados a la persona, sin los eliminados, los que siguen en curso y los que llegaron a un
// estado final hace poco (un incidente terminado ya no cambia, así que su updated_at es cuándo terminó). Usa el
// índice dashboard_assignee, que empieza por assigned_to. Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Infrastructure;

use App\Modules\Incidents\Application\Ports\IncidentCatalog;
use App\Modules\Incidents\Application\Ports\MyIncidentsQuery;
use App\Modules\Incidents\Infrastructure\Models\Incident;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Builder;

final readonly class EloquentMyIncidents implements MyIncidentsQuery
{
    public function __construct(
        private IncidentMapper $mapper,
        private IncidentCatalog $catalog,
    ) {}

    public function assignedTo(int $userId, DateTimeImmutable $finishedSince, int $limit): array
    {
        $final = [];
        $open = [];
        foreach ($this->catalog->statuses() as $status) {
            $status->isFinal ? $final[] = $status->id : $open[] = $status->id;
        }

        $items = Incident::query()
            ->where('assigned_to', $userId)
            ->where(function (Builder $query) use ($open, $final, $finishedSince): void {
                $query->whereIn('status_id', $open)
                    ->orWhere(fn (Builder $finished) => $finished->whereIn('status_id', $final)
                        ->where('updated_at', '>=', $finishedSince->format('Y-m-d H:i:s')));
            })
            ->with(IncidentMapper::RELATIONS)
            ->orderBy('due_date')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        return array_values($items->map(fn (Incident $incident) => $this->mapper->toRecord($incident))->all());
    }
}
