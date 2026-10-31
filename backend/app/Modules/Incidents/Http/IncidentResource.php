<?php

declare(strict_types=1);

// Forma pública de un incidente: datos, valores calculados en el back (is_overdue) y lo que la persona puede hacer.
// El front no calcula reglas: muestra los botones según "actions". Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Http;

use App\Modules\Incidents\Application\Data\IncidentRecord;
use App\Modules\Incidents\Application\Data\Person;
use App\Modules\Incidents\Application\Ports\IncidentCatalog;
use App\Modules\Incidents\Domain\Actor;
use App\Modules\Incidents\Domain\IncidentAccess;
use App\Modules\Incidents\Domain\IncidentRules;
use App\Modules\Incidents\Domain\StatusFlags;
use App\Modules\Shared\Application\Ports\Clock;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

final readonly class IncidentResource
{
    public function __construct(
        private IncidentAccess $access,
        private IncidentCatalog $catalog,
        private IncidentRules $rules,
        private Clock $clock,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(IncidentRecord $incident, Actor $actor): array
    {
        $summary = $this->summary($incident, $actor);

        return [
            ...$summary,
            'description' => $incident->description,
            // Solo los estados a los que esta persona puede llevarlo
            'next_statuses' => $summary['actions']['change_status']
                ? array_map(fn (StatusFlags $status): array => ['code' => $status->code, 'name' => $status->name], $this->catalog->nextStatuses($incident->status->id))
                : [],
        ];
    }

    /**
     * Versión liviana para el listado: sin descripción ni estados siguientes.
     *
     * @return array<string, mixed>&array{actions: array<string, bool>}
     */
    public function summary(IncidentRecord $incident, Actor $actor): array
    {
        return [
            'id' => $incident->ulid,
            'code' => $incident->code,
            'title' => $incident->title,
            'priority' => ['code' => $incident->priority->code, 'name' => $incident->priority->name, 'level' => $incident->priority->level],
            'status' => $this->status($incident->status),
            'creator' => $this->person($incident->creator),
            'assignee' => $incident->assignee ? $this->person($incident->assignee) : null,
            'due_date' => $this->date($incident->dueDate),
            'is_overdue' => $this->rules->isOverdue($incident->status, $incident->dueDate, $this->clock->now()),
            'resolved_at' => $this->date($incident->resolvedAt),
            'created_at' => $this->date($incident->createdAt),
            'updated_at' => $this->date($incident->updatedAt),
            'deleted_at' => $this->date($incident->deletedAt),
            'version' => $incident->version,
            'actions' => $this->access->actions($actor, $incident->state()),
        ];
    }

    /**
     * @return array{code: string, name: string, is_final: bool}
     */
    private function status(StatusFlags $status): array
    {
        return ['code' => $status->code, 'name' => $status->name, 'is_final' => $status->isFinal];
    }

    /**
     * @return array{id: string, name: string}
     */
    private function person(Person $person): array
    {
        return $person->snapshot();
    }

    private function date(?DateTimeImmutable $date): ?string
    {
        return $date?->setTimezone(new DateTimeZone('UTC'))->format(DateTimeInterface::ATOM);
    }
}
