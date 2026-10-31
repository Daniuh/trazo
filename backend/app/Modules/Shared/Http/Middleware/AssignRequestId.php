<?php

declare(strict_types=1);

// Da a cada petición un identificador que viaja en la respuesta, en los errores y en los logs.
// Documentado en docs/backend/shared.md

namespace App\Modules\Shared\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class AssignRequestId
{
    public const HEADER = 'X-Request-Id';

    private const ATTRIBUTE = 'request_id';

    public function handle(Request $request, Closure $next): Response
    {
        $id = self::current($request);
        Log::shareContext(['request_id' => $id]);

        $response = $next($request);
        $response->headers->set(self::HEADER, $id);

        return $response;
    }

    // Reutiliza el id recibido solo si tiene un formato seguro; si no, genera uno nuevo
    public static function current(Request $request): string
    {
        if (! $request->attributes->has(self::ATTRIBUTE)) {
            $incoming = (string) $request->headers->get(self::HEADER, '');
            $id = preg_match('/^[A-Za-z0-9-]{8,64}$/', $incoming) === 1 ? $incoming : (string) Str::ulid();
            $request->attributes->set(self::ATTRIBUTE, $id);
        }

        return (string) $request->attributes->get(self::ATTRIBUTE);
    }
}
