<?php

declare(strict_types=1);

// Solicitudes de reapertura con Eloquent. El índice único de pending_incident_id impide dos pendientes a la vez,
// y la decisión se toma con un UPDATE condicionado al estado pendiente, así dos personas no deciden la misma.
// Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Infrastructure;

use App\Modules\Incidents\Application\Ports\ReopenRequests;
use App\Modules\Incidents\Domain\Denial;
use App\Modules\Incidents\Domain\Exceptions\IncidentExceptions;
use App\Modules\Incidents\Infrastructure\Models\IncidentReopenRequest;
use Illuminate\Database\UniqueConstraintViolationException;

final class EloquentReopenRequests implements ReopenRequests
{
    public function open(int $incidentId, int $requestedBy, string $reason): void
    {
        try {
            IncidentReopenRequest::query()->create([
                'incident_id' => $incidentId,
                'requested_by' => $requestedBy,
                'reason' => $reason,
                'status' => IncidentReopenRequest::PENDING,
            ]);
        } catch (UniqueConstraintViolationException) {
            throw IncidentExceptions::denied(Denial::ReopenPending);
        }
    }

    public function decide(int $incidentId, int $decidedBy, bool $approved, ?string $note): bool
    {
        return IncidentReopenRequest::query()
            ->where('incident_id', $incidentId)
            ->where('status', IncidentReopenRequest::PENDING)
            ->update([
                'status' => $approved ? IncidentReopenRequest::APPROVED : IncidentReopenRequest::REJECTED,
                'decided_by' => $decidedBy,
                'decision_note' => $note,
                'decided_at' => now(),
            ]) === 1;
    }
}
