<?php

declare(strict_types=1);

// Modo mantenimiento en la API: con él activo, todos reciben 503 (SERVICE_UNAVAILABLE) menos quien puede ejecutar
// acciones de sistema, que sigue trabajando y recibe la cabecera X-Maintenance para ver el aviso. El ingreso, el
// segundo paso del MFA y cerrar sesión siguen abiertos para que esa persona pueda entrar. Documentado en docs/backend/system.md

namespace App\Modules\System\Http\Middleware;

use App\Modules\System\Application\Ports\MaintenanceSwitch;
use App\Modules\System\Domain\Operator;
use App\Modules\Users\Infrastructure\UserPermissions;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

final readonly class EnforceMaintenance
{
    public const HEADER = 'X-Maintenance';

    private const ALWAYS_OPEN = ['auth.login', 'auth.mfa.challenge.*', 'auth.logout'];

    public function __construct(
        private MaintenanceSwitch $maintenance,
        private UserPermissions $permissions,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs(...self::ALWAYS_OPEN) || $this->maintenance->current() === null) {
            return $next($request);
        }

        $userId = $request->user('sanctum')?->getAuthIdentifier();
        if ($userId === null || ! in_array(Operator::ACTIONS, $this->permissions->of((int) $userId), true)) {
            throw new HttpException(503);
        }

        $response = $next($request);
        $response->headers->set(self::HEADER, 'on');

        return $response;
    }
}
