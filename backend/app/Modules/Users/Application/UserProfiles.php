<?php

declare(strict_types=1);

// Arma la ficha completa de una persona: permisos de su rol, excepciones, permisos efectivos e incidentes pendientes
// asignados. Documentado en docs/backend/users.md

namespace App\Modules\Users\Application;

use App\Modules\Users\Application\Data\UserProfile;
use App\Modules\Users\Application\Data\UserRecord;
use App\Modules\Users\Application\Ports\EffectivePermissions;
use App\Modules\Users\Application\Ports\RoleCatalog;
use App\Modules\Users\Application\Ports\UserRepository;

final readonly class UserProfiles
{
    public function __construct(
        private UserRepository $users,
        private RoleCatalog $catalog,
        private EffectivePermissions $permissions,
    ) {}

    public function of(UserRecord $user): UserProfile
    {
        $overrides = $this->users->overrides($user->id);

        return new UserProfile(
            $user,
            $this->rolePermissions($user),
            $overrides['grants'],
            $overrides['denies'],
            $this->permissions->of($user->id),
            $this->users->pendingAssignedIncidents($user->id),
        );
    }

    /**
     * Los permisos que traen todos sus roles juntos.
     *
     * @return list<string>
     */
    public function rolePermissions(UserRecord $user): array
    {
        $codes = [];
        foreach ($user->roles as $role) {
            $codes = [...$codes, ...$this->catalog->permissionsOf($role->id)];
        }
        $codes = array_values(array_unique($codes));
        sort($codes);

        return $codes;
    }
}
