<?php

declare(strict_types=1);

// Arma, desde la petición, quién administra (nivel y permisos) y desde dónde, para pasarlo a los casos de uso.
// Documentado en docs/backend/users.md

namespace App\Modules\Users\Http;

use App\Modules\Shared\Application\Data\RequestContext;
use App\Modules\Users\Application\Administrators;
use App\Modules\Users\Domain\Administrator;
use Illuminate\Http\Request;

final readonly class RequestAdmin
{
    public function __construct(private Administrators $administrators) {}

    public function admin(Request $request): Administrator
    {
        // Estas rutas pasan por auth:sanctum, así que siempre hay usuario
        return $this->administrators->for((int) $request->user()?->getAuthIdentifier());
    }

    public function context(Request $request): RequestContext
    {
        return new RequestContext($request->ip(), $request->userAgent());
    }
}
