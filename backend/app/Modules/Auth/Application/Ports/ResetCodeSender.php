<?php

declare(strict_types=1);

// Envía el código de recuperación al correo de la persona. Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Application\Ports;

use App\Modules\Auth\Application\Password\Account;

interface ResetCodeSender
{
    public function send(Account $account, string $code, int $minutes): void;
}
