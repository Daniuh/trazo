<?php

declare(strict_types=1);

// Arma el usuario de la sesión con roles, permisos y estado del MFA en consultas fijas, sin importar cuántos tenga.
// Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Infrastructure;

use App\Modules\Auth\Application\Mfa\MfaStatus;
use App\Modules\Auth\Application\Ports\MfaPolicies;
use App\Modules\Auth\Application\Ports\MfaStore;
use App\Modules\Auth\Application\Ports\SessionUserReader;
use App\Modules\Auth\Application\SessionUser;
use App\Modules\Users\Infrastructure\Models\User;
use App\Modules\Users\Infrastructure\UserPermissions;
use Illuminate\Support\Facades\DB;

final readonly class EloquentSessionUserReader implements SessionUserReader
{
    public function __construct(
        private UserPermissions $permissions,
        private MfaStore $mfa,
        private MfaPolicies $policies,
    ) {}

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

        $enabled = $this->mfa->find($userId)?->confirmed === true;
        $status = new MfaStatus(
            $enabled,
            $this->policies->requirementFor($userId)->required,
            $enabled ? $this->mfa->remainingRecoveryCodes($userId) : 0,
        );

        return new SessionUser($user->ulid, $user->name, $user->email, $roles, $this->permissions->of($userId), $status);
    }
}
