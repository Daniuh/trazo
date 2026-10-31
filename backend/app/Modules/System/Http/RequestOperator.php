<?php

declare(strict_types=1);

// Arma, desde la petición, quién opera (con sus permisos efectivos) y desde dónde. Documentado en docs/backend/system.md

namespace App\Modules\System\Http;

use App\Modules\Shared\Application\Data\RequestContext;
use App\Modules\System\Domain\Operator;
use App\Modules\Users\Infrastructure\UserPermissions;
use Illuminate\Http\Request;

final readonly class RequestOperator
{
    public function __construct(private UserPermissions $permissions) {}

    public function operator(Request $request): Operator
    {
        // Estas rutas pasan por auth:sanctum, así que siempre hay usuario
        $userId = (int) $request->user()?->getAuthIdentifier();

        return new Operator($userId, $this->permissions->of($userId));
    }

    public function context(Request $request): RequestContext
    {
        return new RequestContext($request->ip(), $request->userAgent());
    }
}
