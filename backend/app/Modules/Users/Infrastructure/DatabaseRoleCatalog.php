<?php

declare(strict_types=1);

// Roles y permisos desde la base, leídos una vez por petición. Documentado en docs/backend/users.md

namespace App\Modules\Users\Infrastructure;

use App\Modules\Users\Application\Data\PermissionInfo;
use App\Modules\Users\Application\Data\RoleInfo;
use App\Modules\Users\Application\Ports\RoleCatalog;
use Illuminate\Support\Facades\DB;

final class DatabaseRoleCatalog implements RoleCatalog
{
    /** @var list<RoleInfo>|null */
    private ?array $roles = null;

    /** @var array<int, list<string>> */
    private array $rolePermissions = [];

    public function roles(): array
    {
        return $this->roles ??= array_values(DB::table('roles')
            ->orderByDesc('level')
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'level'])
            ->map(fn (object $role): RoleInfo => new RoleInfo((int) $role->id, (string) $role->code, (string) $role->name, (int) $role->level))
            ->all());
    }

    public function find(string $code): ?RoleInfo
    {
        foreach ($this->roles() as $role) {
            if ($role->code === $code) {
                return $role;
            }
        }

        return null;
    }

    public function permissionsOf(int $roleId): array
    {
        return $this->rolePermissions[$roleId] ??= array_values(DB::table('role_permission')
            ->join('permissions', 'permissions.id', '=', 'role_permission.permission_id')
            ->where('role_permission.role_id', $roleId)
            ->orderBy('permissions.code')
            ->pluck('permissions.code')
            ->map(fn (mixed $code): string => (string) $code)
            ->all());
    }

    public function permissions(): array
    {
        return array_values(DB::table('permissions')
            ->orderBy('code')
            ->get(['code', 'name'])
            ->map(fn (object $permission): PermissionInfo => new PermissionInfo((string) $permission->code, (string) $permission->name))
            ->all());
    }
}
