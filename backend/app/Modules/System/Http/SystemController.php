<?php

declare(strict_types=1);

// Endpoints de la pantalla de sistema: salud, eventos y jobs fallidos (lectura) y las acciones (reiniciar, limpiar
// caché, mantenimiento, reintentar y descartar jobs). Cada uno delega en su caso de uso. Documentado en docs/backend/system.md

namespace App\Modules\System\Http;

use App\Modules\System\Application\Data\EventRecord;
use App\Modules\System\Application\Data\EventType;
use App\Modules\System\Application\Data\FailedJob;
use App\Modules\System\Application\ManageFailedJobs;
use App\Modules\System\Application\ManageSystemEvents;
use App\Modules\System\Application\ReadHealth;
use App\Modules\System\Application\RunSystemAction;
use App\Modules\System\Application\SetMaintenance;
use App\Modules\System\Domain\SystemAction;
use App\Modules\System\Http\Requests\ActionRequest;
use App\Modules\System\Http\Requests\EventListRequest;
use App\Modules\System\Http\Requests\EventStatusRequest;
use App\Modules\System\Http\Requests\MaintenanceRequest;
use App\Modules\System\Http\Requests\RetryJobsRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final readonly class SystemController
{
    public function __construct(
        private RequestOperator $requests,
        private SystemResource $resource,
    ) {}

    public function health(Request $request, ReadHealth $health): JsonResponse
    {
        return new JsonResponse(['data' => $this->resource->health($health->handle($this->requests->operator($request)))]);
    }

    public function events(EventListRequest $request, ManageSystemEvents $events): JsonResponse
    {
        $page = $events->list($this->requests->operator($request), $request->criteria());

        return new JsonResponse([
            'data' => array_map(fn (EventRecord $event): array => $this->resource->event($event), $page->items),
            'meta' => ['next_cursor' => $page->next?->encode()],
        ]);
    }

    public function eventTypes(Request $request, ManageSystemEvents $events): JsonResponse
    {
        $types = $events->types($this->requests->operator($request));

        return new JsonResponse(['data' => array_map(fn (EventType $type): array => $this->resource->type($type), $types)]);
    }

    public function changeEventStatus(EventStatusRequest $request, int $event, ManageSystemEvents $events): JsonResponse
    {
        $record = $events->changeStatus($this->requests->operator($request), $event, $request->status());

        return new JsonResponse(['data' => $this->resource->event($record)]);
    }

    public function failedJobs(Request $request, ManageFailedJobs $jobs): JsonResponse
    {
        $list = $jobs->list($this->requests->operator($request));

        return new JsonResponse([
            'data' => array_map(fn (FailedJob $job): array => $this->resource->failedJob($job), $list->items),
            'meta' => ['total' => $list->total],
        ]);
    }

    public function retryJobs(RetryJobsRequest $request, ManageFailedJobs $jobs): JsonResponse
    {
        $retried = $jobs->retry($this->requests->operator($request), $request->ids(), $request->mfaCode(), $this->requests->context($request));

        return new JsonResponse(['data' => ['retried' => $retried]]);
    }

    public function discardJob(ActionRequest $request, string $job, ManageFailedJobs $jobs): Response
    {
        $jobs->discard($this->requests->operator($request), $job, $request->mfaCode(), $this->requests->context($request));

        return response()->noContent();
    }

    public function action(ActionRequest $request, string $action, RunSystemAction $run): Response
    {
        $run->handle($this->requests->operator($request), SystemAction::from($action), $request->mfaCode(), $this->requests->context($request));

        return response()->noContent();
    }

    public function maintenance(MaintenanceRequest $request, SetMaintenance $maintenance): Response
    {
        $maintenance->handle($this->requests->operator($request), $request->enabled(), $request->mfaCode(), $this->requests->context($request));

        return response()->noContent();
    }
}
