<?php

declare(strict_types=1);

// Caso de uso: crear un incidente. Nace en el estado inicial; si no trae vencimiento, se calcula con la prioridad.
// Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Application;

use App\Modules\Incidents\Application\Data\IncidentInput;
use App\Modules\Incidents\Application\Data\IncidentRecord;
use App\Modules\Incidents\Application\Data\Person;
use App\Modules\Incidents\Application\Ports\ActivityLog;
use App\Modules\Incidents\Application\Ports\AssigneeDirectory;
use App\Modules\Incidents\Application\Ports\IncidentCatalog;
use App\Modules\Incidents\Application\Ports\IncidentRepository;
use App\Modules\Incidents\Domain\Actor;
use App\Modules\Incidents\Domain\Exceptions\IncidentExceptions;
use App\Modules\Incidents\Domain\IncidentAccess;
use App\Modules\Incidents\Domain\IncidentRules;
use App\Modules\Shared\Application\Data\RequestContext;
use App\Modules\Shared\Application\Ports\Clock;
use App\Modules\Shared\Application\Ports\Transaction;
use App\Modules\Shared\Domain\Exceptions\ForbiddenException;
use App\Modules\Shared\Domain\Exceptions\RuleViolationException;
use LogicException;

final readonly class CreateIncident
{
    public function __construct(
        private IncidentRepository $incidents,
        private IncidentCatalog $catalog,
        private AssigneeDirectory $assignees,
        private ActivityLog $activity,
        private Transaction $transaction,
        private Clock $clock,
        private IncidentRules $rules,
        private IncidentAccess $access,
    ) {}

    public function handle(Actor $actor, IncidentInput $input, ?string $assigneeUlid, RequestContext $context): IncidentRecord
    {
        if (! $actor->can('incidents.create')) {
            throw new ForbiddenException;
        }

        $priority = $this->catalog->priority($input->priorityCode) ?? throw new RuleViolationException('INVALID_REFERENCE');
        $assignee = $assigneeUlid === null ? null : $this->assignee($actor, $assigneeUlid);
        $dueDate = $this->rules->dueDate($input->dueDate, $priority->resolutionMinutes, $this->clock->now());

        $ulid = $this->transaction->run(function () use ($actor, $input, $priority, $assignee, $dueDate, $context): string {
            $ulid = $this->incidents->create(
                $input->title,
                $input->description,
                $priority->id,
                $this->catalog->initialStatus()->id,
                $actor->id,
                $assignee?->id,
                $dueDate,
            );
            $incident = $this->find($ulid);
            $this->activity->record($incident->id, $actor->id, 'created', $assignee ? ['assignee' => ['from' => null, 'to' => $assignee->snapshot()]] : null, $context);

            return $ulid;
        });

        return $this->find($ulid);
    }

    // Asignar al crear: a cualquiera con "asignar"; a sí mismo con "tomar". Quien no puede asignar a otros recibe
    // siempre 403, para no revelar a quién se podría asignar
    private function assignee(Actor $actor, string $ulid): Person
    {
        $person = $this->assignees->find($ulid);
        if ($person === null) {
            throw $actor->can('incidents.assign') ? IncidentExceptions::invalidAssignee() : new ForbiddenException;
        }
        if (! $this->access->canAssignOnCreate($actor, $person->id)) {
            throw new ForbiddenException;
        }

        return $person;
    }

    private function find(string $ulid): IncidentRecord
    {
        return $this->incidents->find($ulid) ?? throw new LogicException('El incidente recién creado no se encontró.');
    }
}
