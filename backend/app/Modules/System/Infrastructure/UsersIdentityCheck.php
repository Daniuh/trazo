<?php

declare(strict_types=1);

// Puente con la reautenticación del módulo de usuarios: el mismo código de la aplicación, sin repetición y con
// pausa tras 5 intentos fallidos. Documentado en docs/backend/system.md

namespace App\Modules\System\Infrastructure;

use App\Modules\System\Application\Ports\IdentityCheck;
use App\Modules\Users\Application\Ports\AccountSecurity;

final readonly class UsersIdentityCheck implements IdentityCheck
{
    public function __construct(private AccountSecurity $security) {}

    public function confirm(int $userId, ?string $code): void
    {
        $this->security->confirmIdentity($userId, $code);
    }
}
