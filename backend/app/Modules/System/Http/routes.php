<?php

declare(strict_types=1);

// Rutas de la pantalla de sistema (/api/v1/system). Todas exigen sesión; las acciones, además, 5 por minuto.
// Documentado en docs/backend/system.md

use App\Modules\System\Domain\SystemAction;
use App\Modules\System\Http\SystemController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('system')->controller(SystemController::class)->group(function (): void {
    Route::get('health', 'health');
    Route::get('events', 'events');
    Route::get('events/types', 'eventTypes');
    Route::patch('events/{event}', 'changeEventStatus')->whereNumber('event');
    Route::get('failed-jobs', 'failedJobs');

    Route::middleware('throttle:system-actions')->group(function (): void {
        Route::post('failed-jobs/retry', 'retryJobs');
        Route::delete('failed-jobs/{job}', 'discardJob')->whereUuid('job');
        Route::post('actions/{action}', 'action')->whereIn('action', array_map(fn (SystemAction $action): string => $action->value, SystemAction::cases()));
        Route::put('maintenance', 'maintenance');
    });
});
