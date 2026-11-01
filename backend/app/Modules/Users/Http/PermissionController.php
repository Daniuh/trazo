<?php

declare(strict_types=1);

// Permisos para los formularios de la administración (personas y roles): su módulo, si son sensibles y si quien
// consulta los puede conceder (los que él mismo tiene). Documentado en docs/backend/users.md

namespace App\Modules\Users\Http;

use App\Modules\Users\Application\Data\PermissionInfo;
use App\Modules\Users\Application\Ports\RoleCatalog;
use App\Modules\Users\Application\RoleGate;
use App\Modules\Users\Domain\SensitivePermissions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class PermissionController
{
    public function __construct(
        private RequestAdmin $requests,
        private RoleCatalog $catalog,
        private RoleGate $gate,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $admin = $this->requests->admin($request);
        $this->gate->ensureCanView($admin);

        return new JsonResponse(['data' => array_map(fn (PermissionInfo $permission): array => [
            'code' => $permission->code,
            'name' => $permission->name,
            'module' => $permission->module(),
            'sensitive' => SensitivePermissions::is($permission->code),
            'grantable' => $admin->isRoot() || $admin->can($permission->code),
        ], $this->catalog->permissions())]);
    }
}
