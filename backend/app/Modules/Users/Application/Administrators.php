<?php

declare(strict_types=1);

// Arma a quien administra (nivel y permisos efectivos) a partir de su id. Documentado en docs/backend/users.md

namespace App\Modules\Users\Application;

use App\Modules\Users\Application\Ports\EffectivePermissions;
use App\Modules\Users\Application\Ports\UserRepository;
use App\Modules\Users\Domain\Administrator;
use App\Modules\Users\Domain\RoleLevel;

final readonly class Administrators
{
    public function __construct(
        private UserRepository $users,
        private EffectivePermissions $permissions,
    ) {}

    public function for(int $userId): Administrator
    {
        $level = $this->users->findById($userId)?->level() ?? RoleLevel::NONE;

        return new Administrator($userId, $level, $this->permissions->of($userId));
    }
}
