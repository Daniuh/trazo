<?php

declare(strict_types=1);

// Incidentes con Eloquent. La lectura trae catálogos y personas en una sola pasada (eager loading),
// y cada escritura comprueba la versión en el mismo UPDATE. Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Infrastructure;

use App\Modules\Incidents\Application\Data\IncidentRecord;
use App\Modules\Incidents\Application\Ports\IncidentRepository;
use App\Modules\Incidents\Domain\Exceptions\IncidentExceptions;
use App\Modules\Incidents\Infrastructure\Models\Incident;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class EloquentIncidentRepository implements IncidentRepository
{
    private const CODE_FORMAT = 'INC-%06d';

    public function __construct(private IncidentMapper $mapper) {}

    public function find(string $ulid, bool $withDeleted = false): ?IncidentRecord
    {
        $incident = Incident::query()
            ->withTrashed($withDeleted)
            ->with(['priority', 'status', 'creator:id,ulid,name', 'assignee:id,ulid,name'])
            ->where('ulid', $ulid)
            ->first();

        return $incident ? $this->mapper->toRecord($incident) : null;
    }

    public function create(
        string $title,
        string $description,
        int $priorityId,
        int $statusId,
        int $createdBy,
        ?int $assignedTo,
        DateTimeImmutable $dueDate,
    ): string {
        $incident = Incident::query()->create([
            // Código provisional y único: el definitivo sale del id, que solo existe después de insertar
            'code' => 'NEW-'.Str::lower(Str::random(16)),
            'title' => $title,
            'description' => $description,
            'priority_id' => $priorityId,
            'status_id' => $statusId,
            'created_by' => $createdBy,
            'assigned_to' => $assignedTo,
            'due_date' => $dueDate,
        ]);

        $incident->forceFill(['code' => sprintf(self::CODE_FORMAT, $incident->id)])->saveQuietly();

        return $incident->ulid;
    }

    public function update(IncidentRecord $incident, int $expectedVersion, array $columns): void
    {
        $updated = Incident::query()
            ->whereKey($incident->id)
            ->where('lock_version', $expectedVersion)
            ->update([...$columns, 'lock_version' => DB::raw('lock_version + 1')]);

        if ($updated === 0) {
            throw IncidentExceptions::versionConflict();
        }
    }

    public function delete(IncidentRecord $incident): void
    {
        Incident::query()->whereKey($incident->id)->delete();
    }

    public function restore(IncidentRecord $incident): void
    {
        Incident::onlyTrashed()->whereKey($incident->id)->restore();
    }
}
