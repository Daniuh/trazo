<?php

declare(strict_types=1);

// Forma de un rol en la API: datos, permisos, política de MFA, cuántas personas lo tienen, si quien consulta lo puede
// dar a una persona y lo que puede hacer con el rol. Documentado en docs/backend/users.md

namespace App\Modules\Users\Http;

use App\Modules\Users\Application\Data\RoleDetail;
use App\Modules\Users\Domain\Administrator;
use App\Modules\Users\Domain\RoleAccess;
use App\Modules\Users\Domain\UserAccess;

final readonly class RoleResource
{
    public function __construct(
        private RoleAccess $roles,
        private UserAccess $users,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(RoleDetail $role, Administrator $admin): array
    {
        return [
            'code' => $role->role->code,
            'name' => $role->role->name,
            'level' => $role->role->level,
            'is_system' => $role->isSystem,
            'permissions' => $role->permissions,
            'mfa' => ['required' => $role->mfa->required, 'grace_logins' => $role->mfa->graceLogins],
            'members' => $role->members,
            'assignable' => $this->users->canGiveRole($admin, $role->role->level),
            'actions' => $this->roles->actions($admin, $role->managed()),
        ];
    }
}
