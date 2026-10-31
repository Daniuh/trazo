<?php

declare(strict_types=1);

// Permisos efectivos de un usuario (la unión de los de sus roles), en una sola consulta.
// Lo usan la sesión y las reglas de cada módulo. Documentado en docs/base-de-datos/diccionario/roles-y-permisos.md

namespace App\Modules\Users\Infrastructure;

use Illuminate\Support\Facades\DB;

final class UserPermissions
{
    /** @var array<int, list<string>> Una consulta por usuario y petición */
    private array $resolved = [];

    /**
     * @return list<string>
     */
    public function of(int $userId): array
    {
        return $this->resolved[$userId] ??= array_values(DB::table('user_role')
            ->join('role_permission', 'role_permission.role_id', '=', 'user_role.role_id')
            ->join('permissions', 'permissions.id', '=', 'role_permission.permission_id')
            ->where('user_role.user_id', $userId)
            ->distinct()
            ->orderBy('permissions.code')
            ->pluck('permissions.code')
            ->map(fn (mixed $code): string => (string) $code)
            ->all());
    }
}
