<?php

declare(strict_types=1);

// Caso de uso: mover un incidente por su ciclo de vida, solo por las transiciones que define el catálogo.
// Los estados que lo piden en el catálogo (Resuelto, Cancelado) exigen una nota. Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Application;

use App\Modules\Incidents\Application\Data\IncidentRecord;
use App\Modules\Incidents\Application\Ports\ActivityLog;
use App\Modules\Incidents\Application\Ports\IncidentCatalog;
use App\Modules\Incidents\Application\Ports\IncidentRepository;
use App\Modules\Incidents\Domain\Actor;
use App\Modules\Incidents\Domain\Exceptions\IncidentExceptions;
use App\Modules\Incidents\Domain\IncidentAction;
use App\Modules\Incidents\Domain\IncidentRules;
use App\Modules\Incidents\Domain\StatusFlags;
use App\Modules\Shared\Application\Data\RequestContext;
use App\Modules\Shared\Application\Ports\Clock;
use App\Modules\Shared\Application\Ports\Transaction;

final readonly class ChangeIncidentStatus
{
    public function __construct(
        private IncidentGate $gate,
        private IncidentRepository $incidents,
        private IncidentCatalog $catalog,
        private ActivityLog $activity,
        private Transaction $transaction,
        private Clock $clock,
        private IncidentRules $rules,
    ) {}

    public function handle(Actor $actor, string $ulid, int $version, string $statusCode, ?string $note, RequestContext $context): IncidentRecord
    {
        $incident = $this->gate->load($actor, $ulid, IncidentAction::ChangeStatus);
        $this->gate->ensureVersion($incident, $version);

        $target = $this->allowedTarget($incident, $statusCode);
        if ($target->requiresNote && $note === null) {
            throw IncidentExceptions::noteRequired($target->code);
        }
        $resolvedAt = $this->rules->resolvedAt($target, $incident->resolvedAt, $this->clock->now());

        $this->transaction->run(function () use ($incident, $version, $target, $resolvedAt, $actor, $note, $context): void {
            $this->incidents->update($incident, $version, ['status_id' => $target->id, 'resolved_at' => $resolvedAt]);
            $this->activity->record($incident->id, $actor->id, 'status_changed', [
                'status' => ['from' => $incident->status->snapshot(), 'to' => $target->snapshot()],
            ], $context, $note);
        });

        return $this->incidents->find($ulid) ?? $incident;
    }

    private function allowedTarget(IncidentRecord $incident, string $statusCode): StatusFlags
    {
        foreach ($this->catalog->nextStatuses($incident->status->id) as $next) {
            if ($next->code === $statusCode) {
                return $next;
            }
        }

        throw IncidentExceptions::invalidTransition($incident->status->code, $statusCode);
    }
}
