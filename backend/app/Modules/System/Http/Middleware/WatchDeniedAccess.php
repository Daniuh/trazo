<?php

declare(strict_types=1);

// Accesos denegados repetidos: una persona con sesión que recibe 10 respuestas 403 en 5 minutos está probando
// lo que no le corresponde (o tiene la interfaz desactualizada). Se registra una vez por ventana; un 403 aislado no.
// Documentado en docs/backend/system.md

namespace App\Modules\System\Http\Middleware;

use App\Modules\Shared\Application\Ports\SystemEvents;
use App\Modules\Shared\Application\SystemEventType;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

final readonly class WatchDeniedAccess
{
    public const THRESHOLD = 10;

    private const WINDOW_SECONDS = 300;

    public function __construct(private SystemEvents $events) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $userId = $request->user('sanctum')?->getAuthIdentifier();
        if ($response->getStatusCode() === Response::HTTP_FORBIDDEN && $userId !== null) {
            if (RateLimiter::hit("denied:{$userId}", self::WINDOW_SECONDS) === self::THRESHOLD) {
                $this->events->record(SystemEventType::AccessDeniedRepeated, "user:{$userId}", self::THRESHOLD.' accesos denegados en 5 minutos', [
                    'user_id' => (int) $userId,
                    'last_path' => '/'.ltrim($request->path(), '/'),
                ]);
            }
        }

        return $response;
    }
}
