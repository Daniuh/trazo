<?php

declare(strict_types=1);

// Correo o contraseña incorrectos. El mismo error sirve si el correo no existe o la cuenta está inactiva,
// para no revelar qué cuentas existen. Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Domain\Exceptions;

use App\Modules\Shared\Domain\Exceptions\AuthenticationFailedException;

final class InvalidCredentialsException extends AuthenticationFailedException
{
    public function __construct()
    {
        parent::__construct('INVALID_CREDENTIALS');
    }
}
