<?php

declare(strict_types=1);

// Única fuente de quién puede crear, editar o eliminar roles. Misma jerarquía que con las personas: solo se trabaja
// con roles por debajo del propio nivel, y el rol de la cima no se toca. Documentado en docs/backend/users.md

namespace App\Modules\Users\Domain;

final class RoleAccess
{
    public const PERMISSION = 'roles.manage';

    // La cima es solo del Super admin: ningún otro rol puede llegar ahí
    public const MAX_LEVEL = RoleLevel::ROOT - 1;

    /** Null si puede; si no, el motivo. */
    public function denial(RoleAction $action, Administrator $admin, ManagedRole $role): ?RoleDenial
    {
        if (! $admin->can(self::PERMISSION)) {
            return RoleDenial::Forbidden;
        }
        if ($role->level >= RoleLevel::ROOT) {
            return RoleDenial::Locked;
        }
        if (! $admin->outranks($role->level)) {
            return RoleDenial::Outranked;
        }

        return match ($action) {
            RoleAction::Update => null,
            RoleAction::Delete => $role->isSystem ? RoleDenial::System : ($role->members > 0 ? RoleDenial::InUse : null),
        };
    }

    /**
     * @return array<string, bool>
     */
    public function actions(Administrator $admin, ManagedRole $role): array
    {
        $actions = [];
        foreach (RoleAction::cases() as $action) {
            $actions[$action->value] = $this->denial($action, $admin, $role) === null;
        }

        return $actions;
    }

    public function canCreate(Administrator $admin): bool
    {
        return $admin->can(self::PERMISSION);
    }

    // Un rol solo se ubica por debajo de quien lo crea o lo edita, y nunca en la cima
    public function canUseLevel(Administrator $admin, int $level): bool
    {
        return $level >= 1 && $level <= self::MAX_LEVEL && $admin->outranks($level);
    }
}
