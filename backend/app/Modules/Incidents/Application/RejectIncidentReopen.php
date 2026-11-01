<?php

declare(strict_types=1);

// Caso de uso: rechazar la solicitud de reapertura pendiente, explicando por qué. El incidente sigue como estaba.
// Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Application;

use App\Modules\Incidents\Application\Data\IncidentRecord;
use App\Modules\Incidents\Application\Ports\ActivityLog;
use App\Modules\Incidents\Application\Ports\IncidentRepository;
use App\Modules\Incidents\Application\Ports\ReopenRequests;
use App\Modules\Incidents\Domain\Actor;
use App\Modules\Incidents\Domain\Denial;
use App\Modules\Incidents\Domain\Exceptions\IncidentExceptions;
use App\Modules\Incidents\Domain\IncidentAction;
use App\Modules\Shared\Application\Data\RequestContext;
use App\Modules\Shared\Application\Ports\Transaction;

final readonly class RejectIncidentReopen
{
    public function __construct(
        private IncidentGate $gate,
        private IncidentRepository $incidents,
        private ReopenRequests $requests,
        private ActivityLog $activity,
        private Transaction $transaction,
    ) {}

    public function handle(Actor $actor, string $ulid, string $reason, RequestContext $context): IncidentRecord
    {
        $incident = $this->gate->load($actor, $ulid, IncidentAction::DecideReopen);

        $this->transaction->run(function () use ($incident, $actor, $reason, $context): void {
            // Otra persona pudo decidirla mientras tanto
            if (! $this->requests->decide($incident->id, $actor->id, false, $reason)) {
                throw IncidentExceptions::denied(Denial::NoReopenRequest);
            }
            $this->activity->record($incident->id, $actor->id, 'reopen_rejected', null, $context, $reason);
            $this->incidents->announce($incident);
        });

        return $this->incidents->find($ulid) ?? $incident;
    }
}
