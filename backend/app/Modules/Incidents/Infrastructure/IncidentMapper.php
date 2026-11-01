<?php

declare(strict_types=1);

// Traduce el modelo Eloquent de un incidente (con sus relaciones ya cargadas) a los datos de la aplicación.
// Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Infrastructure;

use App\Modules\Incidents\Application\Data\IncidentRecord;
use App\Modules\Incidents\Application\Data\Person;
use App\Modules\Incidents\Application\Data\PriorityInfo;
use App\Modules\Incidents\Application\Data\ReopenRequestInfo;
use App\Modules\Incidents\Domain\StatusFlags;
use App\Modules\Incidents\Infrastructure\Models\Incident;
use App\Modules\Incidents\Infrastructure\Models\Priority;
use App\Modules\Incidents\Infrastructure\Models\Status;
use App\Modules\Users\Infrastructure\Models\User;
use Carbon\CarbonInterface;
use DateTimeImmutable;
use Illuminate\Support\Carbon;
use LogicException;

final class IncidentMapper
{
    // Lo que toRecord necesita cargado; el listado y el detalle usan la misma lista para dar las mismas acciones
    public const array RELATIONS = ['priority', 'status', 'creator:id,ulid,name', 'assignee:id,ulid,name', 'pendingReopenRequest.requester:id,ulid,name'];

    public function toRecord(Incident $incident): IncidentRecord
    {
        return new IncidentRecord(
            $incident->id,
            $incident->ulid,
            $incident->code,
            $incident->title,
            $incident->description,
            $this->priority(self::loaded($incident->priority)),
            $this->status(self::loaded($incident->status)),
            $this->person(self::loaded($incident->creator)),
            $incident->assignee ? $this->person($incident->assignee) : null,
            $incident->due_date->toDateTimeImmutable(),
            $incident->resolved_at?->toDateTimeImmutable(),
            $this->date($incident->created_at),
            $this->date($incident->updated_at),
            $incident->deleted_at?->toDateTimeImmutable(),
            $incident->lock_version,
            $this->reopenRequest($incident),
        );
    }

    private function reopenRequest(Incident $incident): ?ReopenRequestInfo
    {
        $request = $incident->relationLoaded('pendingReopenRequest') ? $incident->pendingReopenRequest : null;

        return $request === null ? null : new ReopenRequestInfo(
            $this->person(self::loaded($request->requester)),
            $request->reason,
            $request->created_at->toDateTimeImmutable(),
        );
    }

    public function priority(Priority $priority): PriorityInfo
    {
        return new PriorityInfo($priority->id, $priority->code, $priority->name, $priority->level, $priority->default_resolution_minutes);
    }

    public function status(Status $status): StatusFlags
    {
        return new StatusFlags($status->id, $status->code, $status->name, $status->is_initial, $status->is_pending, $status->is_final, $status->requires_note);
    }

    public function person(User $user): Person
    {
        return new Person($user->id, $user->ulid, $user->name);
    }

    /**
     * Las relaciones obligatorias siempre existen (FK NOT NULL); si faltan, la consulta no las cargó.
     *
     * @template T of object
     *
     * @param  T|null  $relation
     * @return T
     */
    public static function loaded(?object $relation): object
    {
        return $relation ?? throw new LogicException('Falta cargar una relación obligatoria del incidente.');
    }

    private function date(?CarbonInterface $date): DateTimeImmutable
    {
        return ($date ?? Carbon::now())->toDateTimeImmutable();
    }
}
