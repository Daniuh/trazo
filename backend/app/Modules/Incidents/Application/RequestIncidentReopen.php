<?php

declare(strict_types=1);

// Caso de uso: pedir que se reabra un incidente cerrado o cancelado, explicando por qué.
// Queda pendiente hasta que alguien con permiso la apruebe o la rechace. Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Application;

use App\Modules\Incidents\Application\Data\IncidentRecord;
use App\Modules\Incidents\Application\Ports\ActivityLog;
use App\Modules\Incidents\Application\Ports\IncidentRepository;
use App\Modules\Incidents\Application\Ports\ReopenRequests;
use App\Modules\Incidents\Domain\Actor;
use App\Modules\Incidents\Domain\IncidentAction;
use App\Modules\Shared\Application\Data\RequestContext;
use App\Modules\Shared\Application\Ports\Transaction;

final readonly class RequestIncidentReopen
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
        $incident = $this->gate->load($actor, $ulid, IncidentAction::RequestReopen);

        $this->transaction->run(function () use ($incident, $actor, $reason, $context): void {
            $this->requests->open($incident->id, $actor->id, $reason);
            $this->activity->record($incident->id, $actor->id, 'reopen_requested', null, $context, $reason);
            // Quien decide se entera al momento, aunque las columnas del incidente no cambien
            $this->incidents->announce($incident);
        });

        return $this->incidents->find($ulid) ?? $incident;
    }
}
