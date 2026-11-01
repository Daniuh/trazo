<?php

declare(strict_types=1);

// Endpoints del dashboard: ver el diseño, leer las métricas, guardar el diseño y volver al original.
// Documentado en docs/backend/dashboard.md

namespace App\Modules\Dashboard\Http;

use App\Modules\Dashboard\Application\Data\DashboardView;
use App\Modules\Dashboard\Application\ManageDashboardLayout;
use App\Modules\Dashboard\Application\ReadDashboardMetrics;
use App\Modules\Dashboard\Http\Requests\MetricsRequest;
use App\Modules\Dashboard\Http\Requests\SaveLayoutRequest;
use App\Modules\Incidents\Http\RequestActor;
use DateTimeInterface;
use DateTimeZone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class DashboardController
{
    public function __construct(
        private RequestActor $requests,
        private ManageDashboardLayout $layouts,
        private DashboardResource $resource,
    ) {}

    public function show(Request $request): JsonResponse
    {
        return $this->respond($this->layouts->show($this->requests->actor($request)));
    }

    public function metrics(MetricsRequest $request, ReadDashboardMetrics $metrics): JsonResponse
    {
        $result = $metrics->handle($this->requests->actor($request), $request->codes(), $request->trendDays());

        return new JsonResponse([
            'data' => $result->data,
            'meta' => ['generated_at' => $result->generatedAt->setTimezone(new DateTimeZone('UTC'))->format(DateTimeInterface::ATOM)],
        ]);
    }

    public function saveLayout(SaveLayoutRequest $request): JsonResponse
    {
        return $this->respond($this->layouts->save($this->requests->actor($request), $request->layouts()));
    }

    public function resetLayout(Request $request): JsonResponse
    {
        return $this->respond($this->layouts->reset($this->requests->actor($request)));
    }

    private function respond(DashboardView $view): JsonResponse
    {
        return new JsonResponse(['data' => $this->resource->toArray($view)]);
    }
}
