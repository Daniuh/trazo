<?php

declare(strict_types=1);

// Roles y permisos para los formularios de la administración, con lo que quien consulta puede dar: los roles por
// debajo de su nivel y los permisos que él mismo tiene. Documentado en docs/backend/users.md

namespace App\Modules\Users\Http;

use App\Modules\Users\Application\Data\PermissionInfo;
use App\Modules\Users\Application\Data\RoleInfo;
use App\Modules\Users\Application\Ports\RoleCatalog;
use App\Modules\Users\Application\UserGate;
use App\Modules\Users\Domain\SensitivePermissions;
use App\Modules\Users\Domain\UserAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class AccessCatalogController
{
    public function __construct(
        private RequestAdmin $requests,
        private RoleCatalog $catalog,
        private UserGate $gate,
        private UserAccess $access,
    ) {}

    public function roles(Request $request): JsonResponse
    {
        $admin = $this->requests->admin($request);
        $this->gate->ensureCanView($admin);

        return new JsonResponse(['data' => array_map(fn (RoleInfo $role): array => [
            'code' => $role->code,
            'name' => $role->name,
            'level' => $role->level,
            'assignable' => $this->access->canGiveRole($admin, $role->level),
            'permissions' => $this->catalog->permissionsOf($role->id),
        ], $this->catalog->roles())]);
    }

    public function permissions(Request $request): JsonResponse
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
