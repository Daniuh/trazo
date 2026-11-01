<?php

declare(strict_types=1);

// Permisos sensibles: los de administrar personas, roles o el sistema. Darlos o quitarlos, o dar un rol que los trae,
// exige que quien administra confirme con el código de su aplicación. Documentado en docs/backend/users.md

namespace App\Modules\Users\Domain;

final class SensitivePermissions
{
    private const MODULES = ['users', 'roles', 'system'];

    public static function is(string $permission): bool
    {
        return in_array(explode('.', $permission, 2)[0], self::MODULES, true);
    }

    /**
     * @param  list<string>  $permissions
     */
    public static function any(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (self::is($permission)) {
                return true;
            }
        }

        return false;
    }
}
