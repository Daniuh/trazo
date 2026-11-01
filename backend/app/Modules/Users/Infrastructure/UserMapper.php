<?php

declare(strict_types=1);

// Arma la consulta base de personas (con sus roles y si tienen el MFA activo) y la traduce a UserRecord.
// La usan el repositorio y el listado, así las dos devuelven lo mismo. Documentado en docs/backend/users.md

namespace App\Modules\Users\Infrastructure;

use App\Modules\Users\Application\Data\RoleInfo;
use App\Modules\Users\Application\Data\UserRecord;
use App\Modules\Users\Infrastructure\Models\Role;
use App\Modules\Users\Infrastructure\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class UserMapper
{
    // Tiene el MFA activo: un factor confirmado
    public const MFA_ENABLED_SQL = 'EXISTS (SELECT 1 FROM user_mfa_factors f WHERE f.user_id = users.id AND f.confirmed_at IS NOT NULL)';

    /**
     * @return Builder<User>
     */
    public function query(): Builder
    {
        return User::query()
            ->select(['users.id', 'users.ulid', 'users.name', 'users.given_names', 'users.family_names', 'users.email', 'users.is_active', 'users.last_login_at', 'users.created_at'])
            ->selectRaw(self::MFA_ENABLED_SQL.' AS mfa_enabled')
            ->with('roles:id,code,name,level');
    }

    public function toRecord(User $user): UserRecord
    {
        return new UserRecord(
            $user->id,
            $user->ulid,
            $user->name,
            $user->given_names,
            $user->family_names,
            $user->email,
            $user->is_active,
            array_values($user->roles->map(fn (Role $role): RoleInfo => new RoleInfo($role->id, $role->code, $role->name, $role->level))->all()),
            (bool) $user->getAttribute('mfa_enabled'),
            $user->last_login_at?->toDateTimeImmutable(),
            $user->created_at->toDateTimeImmutable(),
        );
    }
}
