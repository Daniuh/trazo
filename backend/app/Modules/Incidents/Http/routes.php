<?php

declare(strict_types=1);

// Rutas de incidentes y catálogos (/api/v1). Todas exigen sesión. Documentado en docs/backend/incidents.md

use App\Modules\Incidents\Http\CatalogController;
use App\Modules\Incidents\Http\IncidentController;
use App\Modules\Incidents\Http\IncidentWorkflowController;
use App\Modules\Shared\Http\Middleware\EnsureIdempotency;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('catalogs', [CatalogController::class, 'catalogs']);

    Route::prefix('incidents')->group(function (): void {
        // Va antes de {incident} para que "assignees" no se lea como un identificador
        Route::get('assignees', [CatalogController::class, 'assignees']);

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
        });
    });
});
