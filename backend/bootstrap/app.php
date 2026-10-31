<?php

declare(strict_types=1);

// Arranque de la aplicación: rutas, middleware global y manejo de errores de la API.
// Documentado en docs/backend/shared.md

use App\Modules\Shared\Http\Errors\ApiExceptionRenderer;
use App\Modules\Shared\Http\Middleware\AssignRequestId;
use App\Modules\Shared\Http\Middleware\ForceJsonResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api/v1',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Van primero para que incluso un 404 de ruta lleve su id y salga en JSON
        $middleware->prepend([AssignRequestId::class, ForceJsonResponse::class]);
        // Límite general de 120 peticiones por minuto (definido en AppServiceProvider)
        $middleware->throttleApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(
            fn (Throwable $e, Request $request) => $request->is('api/*')
                ? app(ApiExceptionRenderer::class)->render($e, $request)
                : null,
        );
    })->create();
