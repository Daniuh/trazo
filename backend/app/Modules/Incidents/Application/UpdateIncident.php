<?php

declare(strict_types=1);

// Caso de uso: editar título, descripción, prioridad y vencimiento. Solo guarda (y registra) lo que cambió.
// Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Application;

use App\Modules\Incidents\Application\Data\IncidentInput;
use App\Modules\Incidents\Application\Data\IncidentRecord;
use App\Modules\Incidents\Application\Data\RequestContext;
use App\Modules\Incidents\Application\Ports\ActivityLog;
use App\Modules\Incidents\Application\Ports\IncidentCatalog;
use App\Modules\Incidents\Application\Ports\IncidentRepository;
use App\Modules\Incidents\Application\Ports\Transaction;
use App\Modules\Incidents\Domain\Actor;
use App\Modules\Incidents\Domain\IncidentAction;
use App\Modules\Shared\Domain\Exceptions\RuleViolationException;
use DateTimeImmutable;
use DateTimeInterface;

final readonly class UpdateIncident
{
    public function __construct(
        private IncidentGate $gate,
        private IncidentRepository $incidents,
        private IncidentCatalog $catalog,
        private ActivityLog $activity,
        private Transaction $transaction,
    ) {}

    public function handle(Actor $actor, string $ulid, int $version, IncidentInput $input, RequestContext $context): IncidentRecord
    {
        $incident = $this->gate->load($actor, $ulid, IncidentAction::Update);
        $this->gate->ensureVersion($incident, $version);

        $priority = $this->catalog->priority($input->priorityCode) ?? throw new RuleViolationException('INVALID_REFERENCE');
        // Sin vencimiento en la edición, se conserva el que tenía
        $dueDate = $input->dueDate ?? $incident->dueDate;

        $columns = [];
        $changes = [];
        $this->compare($columns, $changes, 'title', $incident->title, $input->title);
        $this->compare($columns, $changes, 'description', $incident->description, $input->description);
        if ($priority->id !== $incident->priority->id) {
            $columns['priority_id'] = $priority->id;
            $changes['priority'] = ['from' => $incident->priority->snapshot(), 'to' => $priority->snapshot()];
        }
        if ($dueDate->getTimestamp() !== $incident->dueDate->getTimestamp()) {
            $columns['due_date'] = $dueDate;
            $changes['due_date'] = ['from' => $incident->dueDate->format(DateTimeInterface::ATOM), 'to' => $dueDate->format(DateTimeInterface::ATOM)];
        }

        if ($columns === []) {
            return $incident;
        }

        $this->transaction->run(function () use ($incident, $version, $columns, $actor, $changes, $context): void {
            $this->incidents->update($incident, $version, $columns);
            $this->activity->record($incident->id, $actor->id, 'updated', $changes, $context);
        });

        return $this->incidents->find($ulid) ?? $incident;
    }

    /**
     * @param  array<string, scalar|DateTimeImmutable|null>  $columns
     * @param  array<string, mixed>  $changes
     */
    private function compare(array &$columns, array &$changes, string $field, string $before, string $after): void
    {
        if ($before !== $after) {
            $columns[$field] = $after;
            $changes[$field] = ['from' => $before, 'to' => $after];
        }
    }
}
