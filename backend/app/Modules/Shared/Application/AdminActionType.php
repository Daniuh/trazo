<?php

declare(strict_types=1);

// Tipos de acción administrativa que se registran. El valor es el código del catálogo admin_action_types.
// Documentado en docs/backend/shared.md

namespace App\Modules\Shared\Application;

enum AdminActionType: string
{
    case UserCreated = 'user_created';
    case UserUpdated = 'user_updated';
    case UserActivated = 'user_activated';
    case UserDeactivated = 'user_deactivated';
    case UserRoleChanged = 'user_role_changed';
    case UserPermissionsChanged = 'user_permissions_changed';
    case UserMfaReset = 'user_mfa_reset';
    case UserAccessReset = 'user_access_reset';
    case RoleCreated = 'role_created';
    case RoleUpdated = 'role_updated';
    case RoleDeleted = 'role_deleted';
    case IncidentsExported = 'incidents_exported';
}
