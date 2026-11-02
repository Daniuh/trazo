<?php

declare(strict_types=1);

// Endpoints de incidentes: listar, exportar, crear, ver, editar, eliminar y restaurar. Cada uno delega en su caso de uso.
// Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Http;

use App\Modules\Incidents\Application\CreateIncident;
use App\Modules\Incidents\Application\Data\IncidentRecord;
use App\Modules\Incidents\Application\DeleteIncident;
use App\Modules\Incidents\Application\ExportIncidents;
use App\Modules\Incidents\Application\IncidentGate;
use App\Modules\Incidents\Application\ListIncidents;
use App\Modules\Incidents\Application\ListMyIncidents;
use App\Modules\Incidents\Application\Ports\IncidentComments;
use App\Modules\Incidents\Application\UpdateIncident;
use App\Modules\Incidents\Domain\IncidentAction;
use App\Modules\Incidents\Http\Requests\CreateIncidentRequest;
use App\Modules\Incidents\Http\Requests\ListIncidentsRequest;
use App\Modules\Incidents\Http\Requests\UpdateIncidentRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class IncidentController
{
    public function __construct(
        private RequestActor $requests,
        private IncidentResource $resource,
    ) {}

    public function index(ListIncidentsRequest $request, ListIncidents $list): JsonResponse
    {
        $actor = $this->requests->actor($request);
        $page = $list->handle($actor, $request->criteria());

        return new JsonResponse([
            'data' => array_map(fn (IncidentRecord $incident): array => $this->resource->summary($incident, $actor), $page->items),
            'meta' => [
                'page' => $page->page,
                'per_page' => $page->perPage,
                'total' => $page->total,
                'last_page' => $page->lastPage(),
                // Total de cada grupo de la página con los mismos filtros (vacío si no se agrupa)
                'groups' => array_map(fn (string $key, int $total): array => ['key' => $key, 'total' => $total], array_keys($page->groups), $page->groups),
            ],
        ]);
    }

    // "Mis incidentes": todos los del tablero de una vez (sin páginas), con los estados a los que se puede mover cada uno
    public function mine(Request $request, ListMyIncidents $list): JsonResponse
    {
        $actor = $this->requests->actor($request);
        $result = $list->handle($actor);

        return new JsonResponse([
            'data' => array_map(fn (IncidentRecord $incident): array => $this->resource->board($incident, $actor), $result['items']),
            'meta' => ['truncated' => $result['truncated'], 'limit' => ListMyIncidents::LIMIT, 'finished_days' => ListMyIncidents::FINISHED_DAYS],
        ]);
    }

    // Mismos filtros, búsqueda y orden del listado; la página no aplica
    public function export(ListIncidentsRequest $request, ExportIncidents $export, IncidentCsv $csv): StreamedResponse
    {
        return $csv->download($export->handle($this->requests->actor($request), $request->criteria(), $this->requests->context($request)));
    }

    public function store(CreateIncidentRequest $request, CreateIncident $create): JsonResponse
    {
        $actor = $this->requests->actor($request);
        $incident = $create->handle($actor, $request->incidentInput(), $request->assignee(), $this->requests->context($request));

        return new JsonResponse(['data' => $this->resource->toArray($incident, $actor)], 201);
    }

    public function show(Request $request, string $incident, IncidentGate $gate, IncidentComments $comments): JsonResponse
    {
        $actor = $this->requests->actor($request);
        $record = $gate->load($actor, $incident, IncidentAction::View);

        // Solo el detalle trae cuántos mensajes tiene la conversación (el botón lo muestra antes de abrirla)
        return new JsonResponse(['data' => [...$this->resource->toArray($record, $actor), 'comments_count' => $comments->count($record->id)]]);
    }

    public function update(UpdateIncidentRequest $request, string $incident, UpdateIncident $update): JsonResponse
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
