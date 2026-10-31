<?php

declare(strict_types=1);

// Endpoints de incidentes: crear, ver, editar, eliminar y restaurar. Cada uno delega en su caso de uso.
// Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Http;

use App\Modules\Incidents\Application\CreateIncident;
use App\Modules\Incidents\Application\DeleteIncident;
use App\Modules\Incidents\Application\IncidentGate;
use App\Modules\Incidents\Application\UpdateIncident;
use App\Modules\Incidents\Domain\IncidentAction;
use App\Modules\Incidents\Http\Requests\IncidentRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final readonly class IncidentController
{
    public function __construct(
        private RequestActor $requests,
        private IncidentResource $resource,
    ) {}

    public function store(IncidentRequest $request, CreateIncident $create): JsonResponse
    {
        $actor = $this->requests->actor($request);
        $incident = $create->handle($actor, $request->incidentInput(), $request->assignee(), $this->requests->context($request));

        return new JsonResponse(['data' => $this->resource->toArray($incident, $actor)], 201);
    }

    public function show(Request $request, string $incident, IncidentGate $gate): JsonResponse
    {
        $actor = $this->requests->actor($request);

        return new JsonResponse(['data' => $this->resource->toArray($gate->load($actor, $incident, IncidentAction::View), $actor)]);
    }

    public function update(IncidentRequest $request, string $incident, UpdateIncident $update): JsonResponse
    {
        $actor = $this->requests->actor($request);
        $record = $update->handle($actor, $incident, $request->version(), $request->incidentInput(), $this->requests->context($request));

        return new JsonResponse(['data' => $this->resource->toArray($record, $actor)]);
    }

    public function destroy(Request $request, string $incident, DeleteIncident $delete): Response
    {
        $delete->delete($this->requests->actor($request), $incident, $this->requests->context($request));

        return response()->noContent();
    }

    public function restore(Request $request, string $incident, DeleteIncident $delete): JsonResponse
    {
        $actor = $this->requests->actor($request);
        $record = $delete->restore($actor, $incident, $this->requests->context($request));

        return new JsonResponse(['data' => $this->resource->toArray($record, $actor)]);
    }
}
