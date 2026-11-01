<?php

declare(strict_types=1);

// Cambios que la administración le hace a una cuenta y que se avisan por correo. Documentado en docs/backend/users.md

namespace App\Modules\Users\Application;

enum AccountEvent: string
{
    case Activated = 'activated';
    case Deactivated = 'deactivated';
    case RoleChanged = 'role_changed';
    case PermissionsChanged = 'permissions_changed';
    case MfaReset = 'mfa_reset';
    case AccessReset = 'access_reset';
    // Se avisa a la dirección anterior
    case EmailChanged = 'email_changed';
}
