<?php

declare(strict_types=1);

// Caso de uso: reabrir un incidente cerrado o cancelado. Lo hace quien puede aprobar: directamente, con su propio
// motivo, o aprobando la solicitud pendiente (el motivo es el de quien la pidió). Vuelve a "En progreso" si tiene a
// alguien asignado, o al estado inicial si no, y recibe un vencimiento nuevo. Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Application;

use App\Modules\Incidents\Application\Data\IncidentRecord;
use App\Modules\Incidents\Application\Ports\ActivityLog;
use App\Modules\Incidents\Application\Ports\IncidentCatalog;
use App\Modules\Incidents\Application\Ports\IncidentRepository;
use App\Modules\Incidents\Application\Ports\ReopenRequests;
use App\Modules\Incidents\Domain\Actor;
use App\Modules\Incidents\Domain\Denial;
use App\Modules\Incidents\Domain\Exceptions\IncidentExceptions;
use App\Modules\Incidents\Domain\IncidentAction;
use App\Modules\Incidents\Domain\IncidentRules;
use App\Modules\Incidents\Domain\StatusFlags;
use App\Modules\Shared\Application\Data\RequestContext;
use App\Modules\Shared\Application\Ports\Clock;
use App\Modules\Shared\Application\Ports\Transaction;
use DateTimeInterface;
use LogicException;

final readonly class ReopenIncident
{
    public function __construct(
        private IncidentGate $gate,
        private IncidentRepository $incidents,
        private IncidentCatalog $catalog,
        private ReopenRequests $requests,
        private ActivityLog $activity,
        private Transaction $transaction,
        private Clock $clock,
        private IncidentRules $rules,
    ) {}

    public function handle(Actor $actor, string $ulid, int $version, string $reason, RequestContext $context): IncidentRecord
    {
        $incident = $this->gate->load($actor, $ulid, IncidentAction::Reopen);
        $this->gate->ensureVersion($incident, $version);

        $this->transaction->run(fn () => $this->reopen($incident, $version, $actor, $reason, $context));

        return $this->incidents->find($ulid) ?? $incident;
    }

    public function approve(Actor $actor, string $ulid, int $version, RequestContext $context): IncidentRecord
    {
        $incident = $this->gate->load($actor, $ulid, IncidentAction::DecideReopen);
        $this->gate->ensureVersion($incident, $version);
        $reason = $incident->reopenRequest->reason ?? throw IncidentExceptions::denied(Denial::NoReopenRequest);

        $this->transaction->run(function () use ($incident, $version, $actor, $reason, $context): void {
            // Otra persona pudo decidirla mientras tanto
            if (! $this->requests->decide($incident->id, $actor->id, true, null)) {
                throw IncidentExceptions::denied(Denial::NoReopenRequest);
            }
            $this->reopen($incident, $version, $actor, $reason, $context);
        });

        return $this->incidents->find($ulid) ?? $incident;
    }

    private function reopen(IncidentRecord $incident, int $version, Actor $actor, string $reason, RequestContext $context): void
    {
        $target = $this->target($incident);
        $dueDate = $this->rules->dueDate(null, $incident->priority->resolutionMinutes, $this->clock->now());

        $this->incidents->update($incident, $version, ['status_id' => $target->id, 'resolved_at' => null, 'due_date' => $dueDate]);
        $this->activity->record($incident->id, $actor->id, 'reopened', [
            'status' => ['from' => $incident->status->snapshot(), 'to' => $target->snapshot()],
            'due_date' => ['from' => $incident->dueDate->format(DateTimeInterface::ATOM), 'to' => $dueDate->format(DateTimeInterface::ATOM)],
        ], $context, $reason);
    }

    private function target(IncidentRecord $incident): StatusFlags
    {
        if ($incident->assignee === null) {
            return $this->catalog->initialStatus();
        }

        return $this->catalog->status(IncidentRules::REOPEN_ASSIGNED_STATUS)
            ?? throw new LogicException('El catálogo no tiene el estado al que vuelve un incidente reabierto.');
    }
}
