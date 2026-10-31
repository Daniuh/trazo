<?php

declare(strict_types=1);

// Rutas de incidentes y catálogos (/api/v1). Todas exigen sesión. Documentado en docs/backend/incidents.md

use App\Modules\Incidents\Http\CatalogController;
use App\Modules\Incidents\Http\IncidentCommentController;
use App\Modules\Incidents\Http\IncidentController;
use App\Modules\Incidents\Http\IncidentReopenController;
use App\Modules\Incidents\Http\IncidentWorkflowController;
use App\Modules\Shared\Http\Middleware\EnsureIdempotency;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('catalogs', [CatalogController::class, 'catalogs']);

    Route::prefix('incidents')->group(function (): void {
        // Van antes de {incident} para que "assignees" o "mine" no se lean como un identificador
        Route::get('assignees', [CatalogController::class, 'assignees']);
        Route::get('mine', [IncidentController::class, 'mine']);
        Route::get('export', [IncidentController::class, 'export'])->middleware('throttle:export');

        Route::get('/', [IncidentController::class, 'index']);
        Route::post('/', [IncidentController::class, 'store'])->middleware(EnsureIdempotency::class);

        Route::prefix('{incident}')->whereUlid('incident')->group(function (): void {
            Route::get('/', [IncidentController::class, 'show']);
            Route::put('/', [IncidentController::class, 'update']);
            Route::delete('/', [IncidentController::class, 'destroy']);
            Route::post('restore', [IncidentController::class, 'restore']);
            Route::put('status', [IncidentWorkflowController::class, 'status'])->name('incidents.status');
            Route::put('assignee', [IncidentWorkflowController::class, 'assignee'])->name('incidents.assignee');
            Route::get('activities', [IncidentWorkflowController::class, 'activities']);
            Route::post('reopen', [IncidentReopenController::class, 'reopen'])->name('incidents.reopen');
            Route::post('reopen-request', [IncidentReopenController::class, 'request'])->name('incidents.reopen.request');
            Route::post('reopen-request/approve', [IncidentReopenController::class, 'approve'])->name('incidents.reopen.approve');
            Route::post('reopen-request/reject', [IncidentReopenController::class, 'reject'])->name('incidents.reopen.reject');
            Route::get('comments', [IncidentCommentController::class, 'index']);
            Route::post('comments', [IncidentCommentController::class, 'store'])->middleware('throttle:comments');
            Route::delete('comments/{comment}', [IncidentCommentController::class, 'destroy'])->whereUlid('comment');
        });
    });
});
