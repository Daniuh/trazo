<?php

declare(strict_types=1);

// Endpoints del flujo de trabajo: cambio de estado, asignación e historial de un incidente.
// Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Http;

use App\Modules\Incidents\Application\AssignIncident;
use App\Modules\Incidents\Application\ChangeIncidentStatus;
use App\Modules\Incidents\Application\IncidentGate;
use App\Modules\Incidents\Application\Ports\ActivityReader;
use App\Modules\Incidents\Domain\IncidentAction;
use App\Modules\Incidents\Http\Requests\WorkflowRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class IncidentWorkflowController
{
    private const ACTIVITY_PAGE_SIZE = 50;

    public function __construct(
        private RequestActor $requests,
        private IncidentResource $resource,
    ) {}

    public function status(WorkflowRequest $request, string $incident, ChangeIncidentStatus $change): JsonResponse
    {
        $actor = $this->requests->actor($request);
        $record = $change->handle($actor, $incident, $request->version(), $request->status(), $request->note(), $this->requests->context($request));

        return new JsonResponse(['data' => $this->resource->toArray($record, $actor)]);
    }

    public function assignee(WorkflowRequest $request, string $incident, AssignIncident $assign): JsonResponse
    {
        $actor = $this->requests->actor($request);
        $record = $assign->handle($actor, $incident, $request->version(), $request->assignee(), $this->requests->context($request));

        return new JsonResponse(['data' => $this->resource->toArray($record, $actor)]);
    }

    // Quien puede ver el incidente puede ver su historial
    public function activities(Request $request, string $incident, IncidentGate $gate, ActivityReader $reader): JsonResponse
    {
        $record = $gate->load($this->requests->actor($request), $incident, IncidentAction::View);
        $page = max(1, $request->integer('page', 1));
        $result = $reader->forIncident($record->id, $page, self::ACTIVITY_PAGE_SIZE);

        return new JsonResponse([
            'data' => $result['items'],
            'meta' => ['page' => $result['page'], 'per_page' => $result['per_page'], 'total' => $result['total']],
        ]);
    }
}
