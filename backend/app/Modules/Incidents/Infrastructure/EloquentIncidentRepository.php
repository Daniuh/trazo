<?php

declare(strict_types=1);

// Incidentes con Eloquent. La lectura trae catálogos y personas en una sola pasada (eager loading),
// y cada escritura comprueba la versión en el mismo UPDATE y avisa del cambio (IncidentChanged).
// Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Infrastructure;

use App\Modules\Incidents\Application\Data\IncidentRecord;
use App\Modules\Incidents\Application\Events\IncidentChange;
use App\Modules\Incidents\Application\Events\IncidentChanged;
use App\Modules\Incidents\Application\Ports\IncidentRepository;
use App\Modules\Incidents\Domain\Exceptions\IncidentExceptions;
use App\Modules\Incidents\Infrastructure\Models\Incident;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

final readonly class EloquentIncidentRepository implements IncidentRepository
{
    public function __construct(private IncidentMapper $mapper, private IncidentCodes $codes) {}

    public function find(string $ulid, bool $withDeleted = false): ?IncidentRecord
    {
        $incident = Incident::query()
            ->withTrashed($withDeleted)
            ->with(IncidentMapper::RELATIONS)
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
            'code' => $this->codes->next(now()->toDateTimeImmutable()),
            'title' => $title,
            'description' => $description,
            'priority_id' => $priorityId,
            'status_id' => $statusId,
            'created_by' => $createdBy,
            'assigned_to' => $assignedTo,
            'due_date' => $dueDate,
        ]);

        event(new IncidentChanged($incident->id, $incident->ulid, IncidentChange::Created, $createdBy, null, $assignedTo));

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

        $assignee = array_key_exists('assigned_to', $columns) ? $columns['assigned_to'] : $incident->assignee?->id;
        $this->changed($incident, IncidentChange::Updated, is_int($assignee) ? $assignee : null);
    }

    public function delete(IncidentRecord $incident): void
    {
        Incident::query()->whereKey($incident->id)->delete();
        $this->changed($incident, IncidentChange::Deleted, $incident->assignee?->id);
    }

    public function restore(IncidentRecord $incident): void
    {
        Incident::onlyTrashed()->whereKey($incident->id)->restore();
        $this->changed($incident, IncidentChange::Restored, $incident->assignee?->id);
    }

    public function announce(IncidentRecord $incident): void
    {
        $this->changed($incident, IncidentChange::Updated, $incident->assignee?->id);
    }

    private function changed(IncidentRecord $incident, IncidentChange $change, ?int $assigneeAfter): void
    {
        event(new IncidentChanged($incident->id, $incident->ulid, $change, $incident->creator->id, $incident->assignee?->id, $assigneeAfter));
    }
}
