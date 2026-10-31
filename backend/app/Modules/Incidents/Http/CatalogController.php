<?php

declare(strict_types=1);

// Catálogos para los formularios y filtros, y la lista de personas asignables.
// /catalogs responde con ETag: si nada cambió, el navegador recibe un 304 sin cuerpo. Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Http;

use App\Modules\Incidents\Application\Data\Person;
use App\Modules\Incidents\Application\Data\PriorityInfo;
use App\Modules\Incidents\Application\Ports\AssigneeDirectory;
use App\Modules\Incidents\Application\Ports\IncidentCatalog;
use App\Modules\Incidents\Domain\StatusFlags;
use App\Modules\Shared\Domain\Exceptions\ForbiddenException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class CatalogController
{
    public function __construct(private IncidentCatalog $catalog) {}

    public function catalogs(Request $request): JsonResponse
    {
        $data = [
            'priorities' => array_values(array_map(fn (PriorityInfo $priority): array => [
                'code' => $priority->code,
                'name' => $priority->name,
                'level' => $priority->level,
                'default_resolution_minutes' => $priority->resolutionMinutes,
            ], $this->catalog->priorities())),
            'statuses' => array_values(array_map(fn (StatusFlags $status): array => [
                'code' => $status->code,
                'name' => $status->name,
                'is_initial' => $status->isInitial,
                'is_pending' => $status->isPending,
                'is_final' => $status->isFinal,
                'next' => array_map(fn (StatusFlags $next): string => $next->code, $this->catalog->nextStatuses($status->id)),
            ], $this->catalog->statuses())),
        ];

        $response = new JsonResponse(['data' => $data]);
        $response->setEtag(hash('sha256', (string) $response->getContent()));
        $response->isNotModified($request);

        return $response;
    }

    // Solo quien puede asignar necesita la lista de personas
    public function assignees(Request $request, RequestActor $requests, AssigneeDirectory $assignees): JsonResponse
    {
        if (! $requests->actor($request)->can('incidents.assign')) {
            throw new ForbiddenException;
        }

        return new JsonResponse([
            'data' => array_map(fn (Person $person): array => $person->snapshot(), $assignees->all()),
        ]);
    }
}
