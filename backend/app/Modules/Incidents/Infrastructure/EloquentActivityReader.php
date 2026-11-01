<?php

declare(strict_types=1);

// Historial de un incidente con su tipo y su autor cargados de una vez (sin consultas N+1).
// Usa el índice (incident_id, created_at). Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Infrastructure;

use App\Modules\Incidents\Application\Ports\ActivityReader;
use App\Modules\Incidents\Infrastructure\Models\IncidentActivity;
use DateTimeInterface;

final class EloquentActivityReader implements ActivityReader
{
    public function forIncident(int $incidentId, int $page, int $perPage): array
    {
        $paginator = IncidentActivity::query()
            ->with(['type:id,code,name', 'user:id,ulid,name'])
            ->where('incident_id', $incidentId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage, ['id', 'activity_type_id', 'user_id', 'changes', 'note', 'created_at'], 'page', $page);

        $items = array_values(array_map(function (IncidentActivity $activity): array {
            $type = IncidentMapper::loaded($activity->type);
            $user = IncidentMapper::loaded($activity->user);

            return [
                'type' => ['code' => $type->code, 'name' => $type->name],
                'user' => ['id' => $user->ulid, 'name' => $user->name],
                'changes' => $activity->changes,
                'note' => $activity->note,
                'created_at' => $activity->created_at->format(DateTimeInterface::ATOM),
            ];
        }, $paginator->items()));

        return ['items' => $items, 'total' => $paginator->total(), 'page' => $paginator->currentPage(), 'per_page' => $perPage];
    }
}
