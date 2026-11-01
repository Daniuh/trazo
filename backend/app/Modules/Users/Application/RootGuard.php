<?php

declare(strict_types=1);

// Siempre queda al menos un Super admin activo. Se llama dentro de la transacción del cambio: bloquea a los Super admin
// activos, así dos personas que se quitan el rol al mismo tiempo no dejan el sistema sin ninguno.
// Documentado en docs/backend/users.md

namespace App\Modules\Users\Application;

use App\Modules\Users\Application\Data\UserRecord;
use App\Modules\Users\Application\Ports\UserRepository;
use App\Modules\Users\Domain\Exceptions\UserExceptions;
use App\Modules\Users\Domain\RoleLevel;

final readonly class RootGuard
{
    public function __construct(private UserRepository $users) {}

    // Antes de desactivar a alguien de la cima o de bajarlo de ella
    public function ensureAnotherRemains(UserRecord $user): void
    {
        if ($user->isActive && $user->level() >= RoleLevel::ROOT && $this->users->lockActiveRoots() <= 1) {
            throw UserExceptions::lastRoot();
        }
    }
}
