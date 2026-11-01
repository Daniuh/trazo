<?php

declare(strict_types=1);

// Caso de uso: tomar un incidente, asignarlo a otra persona o dejarlo sin asignar.
// Tomarlo (asignárselo a uno mismo) pide un permiso distinto a asignarlo a otros. Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Application;

use App\Modules\Incidents\Application\Data\IncidentRecord;
use App\Modules\Incidents\Application\Data\Person;
use App\Modules\Incidents\Application\Ports\ActivityLog;
use App\Modules\Incidents\Application\Ports\AssigneeDirectory;
use App\Modules\Incidents\Application\Ports\IncidentRepository;
use App\Modules\Incidents\Domain\Actor;
use App\Modules\Incidents\Domain\Exceptions\IncidentExceptions;
use App\Modules\Incidents\Domain\IncidentAccess;
use App\Modules\Incidents\Domain\IncidentAction;
use App\Modules\Shared\Application\Data\RequestContext;
use App\Modules\Shared\Application\Ports\Transaction;

final readonly class AssignIncident
{
    public function __construct(
        private IncidentGate $gate,
        private IncidentAccess $access,
        private IncidentRepository $incidents,
        private AssigneeDirectory $assignees,
        private ActivityLog $activity,
        private Transaction $transaction,
    ) {}

    public function handle(Actor $actor, string $ulid, int $version, ?string $assigneeUlid, RequestContext $context): IncidentRecord
    {
        $incident = $this->gate->load($actor, $ulid, IncidentAction::View);
        $assignee = $assigneeUlid === null ? null : ($this->assignees->find($assigneeUlid) ?? throw IncidentExceptions::invalidAssignee());

        $this->gate->ensure($actor, $incident, $this->requiredAction($actor, $incident, $assignee));
        $this->gate->ensureVersion($incident, $version);

        if ($assignee?->id === $incident->assignee?->id) {
            return $incident;
        }

        $this->transaction->run(function () use ($incident, $version, $assignee, $actor, $context): void {
            $this->incidents->update($incident, $version, ['assigned_to' => $assignee?->id]);
            $this->activity->record($incident->id, $actor->id, $assignee ? 'assigned' : 'unassigned', [
                'assignee' => ['from' => $incident->assignee?->snapshot(), 'to' => $assignee?->snapshot()],
            ], $context);
        });

        return $this->incidents->find($ulid) ?? $incident;
    }

    // Asignárselo a uno mismo cuenta como "tomar" si tiene ese permiso; cualquier otro caso es "asignar"
    private function requiredAction(Actor $actor, IncidentRecord $incident, ?Person $assignee): IncidentAction
    {
        $takesIt = $assignee?->id === $actor->id
            && $this->access->denial(IncidentAction::Take, $actor, $incident->state()) === null;

        return $takesIt ? IncidentAction::Take : IncidentAction::Assign;
    }
}
