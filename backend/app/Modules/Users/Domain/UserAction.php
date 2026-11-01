<?php

declare(strict_types=1);

// Lo que se puede hacer con una persona desde la administración. El valor es la clave del campo "actions" de la API.
// Documentado en docs/backend/users.md

namespace App\Modules\Users\Domain;

enum UserAction: string
{
    case Update = 'update';
    case Activate = 'activate';
    case Deactivate = 'deactivate';
    case AssignRole = 'assign_role';
    case GrantPermissions = 'grant_permissions';
    case ResetMfa = 'reset_mfa';
    case ResetAccess = 'reset_access';

    // El permiso que exige cada acción
    public function permission(): string
    {
        return match ($this) {
            self::Update => 'users.update',
            self::Activate, self::Deactivate => 'users.activate',
            self::AssignRole => 'users.roles.assign',
            self::GrantPermissions => 'users.permissions.grant',
            self::ResetMfa => 'users.mfa.reset',
            self::ResetAccess => 'users.access.reset',
        };
    }
}
