<?php

declare(strict_types=1);

// Escribe el historial en incident_activities. Los tipos se buscan por su código estable del catálogo.
// Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Infrastructure;

use App\Modules\Incidents\Application\Ports\ActivityLog;
use App\Modules\Incidents\Infrastructure\Models\ActivityType;
use App\Modules\Incidents\Infrastructure\Models\IncidentActivity;
use App\Modules\Shared\Application\Data\RequestContext;
use Illuminate\Support\Str;
use LogicException;

final class EloquentActivityLog implements ActivityLog
{
    /** @var array<string, int>|null */
    private ?array $types = null;

    public function record(int $incidentId, int $userId, string $type, ?array $changes, RequestContext $context): void
    {
        $this->types ??= ActivityType::query()->pluck('id', 'code')->map(fn (mixed $id): int => (int) $id)->all();

        IncidentActivity::query()->create([
            'incident_id' => $incidentId,
            'user_id' => $userId,
            'activity_type_id' => $this->types[$type] ?? throw new LogicException("Tipo de actividad desconocido: {$type}"),
            'changes' => $changes,
            'ip_address' => $context->ip,
            // La columna admite 255 caracteres; algunos navegadores envían más
            'user_agent' => $context->userAgent === null ? null : Str::limit($context->userAgent, 255, ''),
        ]);
    }
}
