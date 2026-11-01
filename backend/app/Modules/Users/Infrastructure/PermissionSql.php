<?php

declare(strict_types=1);

// La misma regla de UserPermissions (rol + concedidos − quitados), pero como filtro SQL: "personas que tienen tal
// permiso". La usan las consultas que eligen gente por permiso, como el selector de "asignar a". Una prueba compara
// ambas. Documentado en docs/base-de-datos/diccionario/roles-y-permisos.md

namespace App\Modules\Users\Infrastructure;

use Closure;
use Illuminate\Contracts\Database\Query\Builder as QueryContract;
use Illuminate\Database\Query\Builder;

final class PermissionSql
{
    // Agrega el filtro a la consulta recibida (de Eloquent o del query builder)
    public static function havingPermission(QueryContract $query, string $permission, string $userColumn = 'users.id'): void
    {
        $permissionId = fn (Builder $sub) => $sub->select('id')->from('permissions')->where('code', $permission);

        $query
            ->where(fn (QueryContract $any) => $any
                ->whereExists(fn (Builder $sub) => $sub->selectRaw('1')
                    ->from('user_role')
                    ->join('role_permission', 'role_permission.role_id', '=', 'user_role.role_id')
                    ->whereColumn('user_role.user_id', $userColumn)
                    ->whereIn('role_permission.permission_id', $permissionId))
                ->orWhereExists(fn (Builder $sub) => self::override($sub, $userColumn, 'grant', $permissionId)))
            ->whereNotExists(fn (Builder $sub) => self::override($sub, $userColumn, 'deny', $permissionId));
    }

    private static function override(Builder $sub, string $userColumn, string $effect, Closure $permissionId): Builder
    {
        return $sub->selectRaw('1')
            ->from('user_permissions')
            ->whereColumn('user_permissions.user_id', $userColumn)
            ->where('user_permissions.effect', $effect)
            ->whereIn('user_permissions.permission_id', $permissionId);
    }
}
