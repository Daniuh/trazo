<?php

declare(strict_types=1);

// Casos de uso: eliminar (borrado lógico) y restaurar un incidente. El historial se conserva siempre.
// Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Application;

use App\Modules\Incidents\Application\Data\IncidentRecord;
use App\Modules\Incidents\Application\Data\RequestContext;
use App\Modules\Incidents\Application\Ports\ActivityLog;
use App\Modules\Incidents\Application\Ports\IncidentRepository;
use App\Modules\Incidents\Application\Ports\Transaction;
use App\Modules\Incidents\Domain\Actor;
use App\Modules\Incidents\Domain\IncidentAction;

final readonly class DeleteIncident
{
    public function __construct(
        private IncidentGate $gate,
        private IncidentRepository $incidents,
        private ActivityLog $activity,
        private Transaction $transaction,
    ) {}

    public function delete(Actor $actor, string $ulid, RequestContext $context): void
    {
        $incident = $this->gate->load($actor, $ulid, IncidentAction::Delete);

        $this->transaction->run(function () use ($incident, $actor, $context): void {
            $this->incidents->delete($incident);
            $this->activity->record($incident->id, $actor->id, 'deleted', null, $context);
        });
    }

    public function restore(Actor $actor, string $ulid, RequestContext $context): IncidentRecord
    {
        $incident = $this->gate->load($actor, $ulid, IncidentAction::Restore);

        $this->transaction->run(function () use ($incident, $actor, $context): void {
            $this->incidents->restore($incident);
            $this->activity->record($incident->id, $actor->id, 'restored', null, $context);
        });

        return $this->incidents->find($ulid) ?? $incident;
    }
}
