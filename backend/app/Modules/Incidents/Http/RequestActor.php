<?php

declare(strict_types=1);

// Arma, desde la petición, quién actúa (con sus permisos) y desde dónde, para pasarlo a los casos de uso.
// Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Http;

use App\Modules\Incidents\Application\Data\RequestContext;
use App\Modules\Incidents\Domain\Actor;
use App\Modules\Users\Infrastructure\UserPermissions;
use Illuminate\Http\Request;

final readonly class RequestActor
{
    public function __construct(private UserPermissions $permissions) {}

    public function actor(Request $request): Actor
    {
        // Estas rutas pasan por auth:sanctum, así que siempre hay usuario
        $userId = (int) $request->user()?->getAuthIdentifier();

        return new Actor($userId, $this->permissions->of($userId));
    }

    public function context(Request $request): RequestContext
    {
        return new RequestContext($request->ip(), $request->userAgent());
    }
}
