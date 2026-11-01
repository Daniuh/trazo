<?php

declare(strict_types=1);

// Permisos efectivos de un usuario en una sola consulta: los de sus roles, más los concedidos a la persona, menos los
// que se le quitaron (si chocan, gana el quitado). Lo usan la sesión y las reglas de cada módulo.
// Documentado en docs/base-de-datos/diccionario/roles-y-permisos.md

namespace App\Modules\Users\Infrastructure;

use App\Modules\Users\Application\Ports\EffectivePermissions;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class UserPermissions implements EffectivePermissions
{
    /** @var array<int, list<string>> Una consulta por usuario y petición */
    private array $resolved = [];

    /**
     * @return list<string>
     */
    public function of(int $userId): array
    {
        return $this->resolved[$userId] ??= array_values(DB::table('permissions')
            ->where(fn (Builder $query) => $query
                ->whereIn('id', fn (Builder $sub) => $sub->select('role_permission.permission_id')
                    ->from('role_permission')
                    ->join('user_role', 'user_role.role_id', '=', 'role_permission.role_id')
                    ->where('user_role.user_id', $userId))
                ->orWhereIn('id', $this->overrides($userId, 'grant')))
            ->whereNotIn('id', $this->overrides($userId, 'deny'))
            ->orderBy('code')
            ->pluck('code')
            ->map(fn (mixed $code): string => (string) $code)
            ->all());
    }

    // Después de cambiar roles o permisos de alguien en esta misma petición
    public function forget(int $userId): void
    {
        unset($this->resolved[$userId]);
    }

    private function overrides(int $userId, string $effect): Builder
    {
        return DB::table('user_permissions')->select('permission_id')
            ->where('user_id', $userId)
            ->where('effect', $effect);
    }
}
