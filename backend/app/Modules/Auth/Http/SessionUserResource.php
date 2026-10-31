<?php

declare(strict_types=1);

// Forma pública del usuario de la sesión. Nunca incluye el id interno ni la contraseña.
// Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Http;

use App\Modules\Auth\Application\SessionUser;

final class SessionUserResource
{
    /**
     * @return array{id: string, name: string, email: string, roles: list<array{code: string, name: string}>, permissions: list<string>, mfa: array{enabled: bool, required: bool, recovery_codes_left: int}}
     */
    public static function from(SessionUser $user): array
    {
        return [
            'id' => $user->ulid,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->roles,
            'permissions' => $user->permissions,
            'mfa' => [
                'enabled' => $user->mfa->enabled,
                'required' => $user->mfa->required,
                'recovery_codes_left' => $user->mfa->recoveryCodesLeft,
            ],
        ];
    }
}
