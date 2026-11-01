<?php

declare(strict_types=1);

// Endpoints de la auditoría global: catálogos de la pantalla, historial de incidentes y registro de administración.
// Las dos listas avanzan por cursor ("cargar más"). Documentado en docs/backend/audit.md

namespace App\Modules\Audit\Http;

use App\Modules\Audit\Application\Data\AdminAuditEntry;
use App\Modules\Audit\Application\Data\AuditPage;
use App\Modules\Audit\Application\Data\IncidentAuditEntry;
use App\Modules\Audit\Application\ListAdminAudit;
use App\Modules\Audit\Application\ListIncidentAudit;
use App\Modules\Audit\Application\ReadAuditCatalog;
use App\Modules\Audit\Http\Requests\AdminAuditRequest;
use App\Modules\Audit\Http\Requests\IncidentAuditRequest;
use App\Modules\Users\Http\RequestAdmin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class AuditController
{
    public function __construct(
        private RequestAdmin $requests,
        private AuditResource $resource,
    ) {}

    public function catalog(Request $request, ReadAuditCatalog $catalog): JsonResponse
    {
        return new JsonResponse(['data' => $this->resource->catalog($catalog->handle($this->requests->admin($request)))]);
    }

    public function incidents(IncidentAuditRequest $request, ListIncidentAudit $list): JsonResponse
    {
        $viewer = $this->requests->admin($request);
        $page = $list->handle($viewer, $request->criteria());

        return $this->respond($page, fn (IncidentAuditEntry $entry): array => $this->resource->incident($entry, $viewer));
    }

    public function admin(AdminAuditRequest $request, ListAdminAudit $list): JsonResponse
    {
        $viewer = $this->requests->admin($request);
        $page = $list->handle($viewer, $request->criteria());

        return $this->respond($page, fn (AdminAuditEntry $entry): array => $this->resource->admin($entry, $viewer));
    }

    /**
     * @template T
     *
     * @param  AuditPage<T>  $page
     * @param  callable(T): array<string, mixed>  $toArray
     */
    private function respond(AuditPage $page, callable $toArray): JsonResponse
    {
        return new JsonResponse([
            'data' => array_map($toArray, $page->items),
            'meta' => [
                'next_cursor' => $page->next?->encode(),
                'from' => AuditResource::date($page->period->from),
                'to' => AuditResource::date($page->period->to),
            ],
        ]);
    }
}
