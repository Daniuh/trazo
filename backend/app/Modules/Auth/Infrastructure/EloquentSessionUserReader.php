<?php

declare(strict_types=1);

// Arma el usuario de la sesión con roles y permisos en tres consultas fijas, sin importar cuántos tenga.
// Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Infrastructure;

use App\Modules\Auth\Application\Ports\SessionUserReader;
use App\Modules\Auth\Application\SessionUser;
use App\Modules\Users\Infrastructure\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class EloquentSessionUserReader implements SessionUserReader
{
    public function find(int $userId): SessionUser
    {
        $user = User::query()->findOrFail($userId, ['id', 'ulid', 'name', 'email']);

        $roles = array_values(DB::table('user_role')
            ->join('roles', 'roles.id', '=', 'user_role.role_id')
            ->where('user_role.user_id', $userId)
            ->orderBy('roles.code')
            ->get(['roles.code', 'roles.name'])
            ->map(fn (object $role): array => ['code' => (string) $role->code, 'name' => (string) $role->name])
            ->all());

        $permissions = array_values(DB::table('user_role')
            ->join('role_permission', 'role_permission.role_id', '=', 'user_role.role_id')
            ->join('permissions', 'permissions.id', '=', 'role_permission.permission_id')
            ->where('user_role.user_id', $userId)
            ->distinct()
            ->orderBy('permissions.code')
            ->pluck('permissions.code')
            ->map(fn (mixed $code): string => (string) $code)
            ->all());

        return new SessionUser($user->ulid, $user->name, $user->email, $roles, $permissions);
    }
}
