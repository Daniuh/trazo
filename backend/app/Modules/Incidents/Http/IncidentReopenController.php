<?php

declare(strict_types=1);

// Endpoints de la reapertura: pedirla, reabrir directamente y aprobar o rechazar la solicitud pendiente.
// Todos responden con el incidente actualizado. Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Http;

use App\Modules\Incidents\Application\Data\IncidentRecord;
use App\Modules\Incidents\Application\RejectIncidentReopen;
use App\Modules\Incidents\Application\ReopenIncident;
use App\Modules\Incidents\Application\RequestIncidentReopen;
use App\Modules\Incidents\Domain\Actor;
use App\Modules\Incidents\Http\Requests\ReopenRequest;
use Illuminate\Http\JsonResponse;

final readonly class IncidentReopenController
{
    public function __construct(
        private RequestActor $requests,
        private IncidentResource $resource,
    ) {}

    public function request(ReopenRequest $request, string $incident, RequestIncidentReopen $open): JsonResponse
    {
        $actor = $this->requests->actor($request);

        return $this->respond($open->handle($actor, $incident, $request->reason(), $this->requests->context($request)), $actor, 201);
    }

    public function reopen(ReopenRequest $request, string $incident, ReopenIncident $reopen): JsonResponse
    {
        $actor = $this->requests->actor($request);

        return $this->respond($reopen->handle($actor, $incident, $request->version(), $request->reason(), $this->requests->context($request)), $actor);
    }

    public function approve(ReopenRequest $request, string $incident, ReopenIncident $reopen): JsonResponse
    {
        $actor = $this->requests->actor($request);

        return $this->respond($reopen->approve($actor, $incident, $request->version(), $this->requests->context($request)), $actor);
    }

    public function reject(ReopenRequest $request, string $incident, RejectIncidentReopen $reject): JsonResponse
    {
        $actor = $this->requests->actor($request);

        return $this->respond($reject->handle($actor, $incident, $request->reason(), $this->requests->context($request)), $actor);
    }

    private function respond(IncidentRecord $record, Actor $actor, int $status = 200): JsonResponse
    {
        return new JsonResponse(['data' => $this->resource->toArray($record, $actor)], $status);
    }
}
