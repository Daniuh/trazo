<?php

declare(strict_types=1);

// Errores de la recuperación y el cambio de contraseña, cada uno con su código estable.
// Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Domain\Exceptions;

use App\Modules\Shared\Domain\Exceptions\RuleViolationException;

final class PasswordExceptions
{
    // El mismo error si el código está mal, venció, se agotaron sus intentos o el correo no existe: no revela nada
    public static function invalidResetCode(): RuleViolationException
    {
        return new RuleViolationException('PASSWORD_RESET_CODE_INVALID');
    }

    // El pase que entregó el código ya se usó o pasaron sus minutos: hay que pedir otro código
    public static function resetExpired(): RuleViolationException
    {
        return new RuleViolationException('PASSWORD_RESET_EXPIRED');
    }

    public static function currentPasswordInvalid(): RuleViolationException
    {
        return new RuleViolationException('CURRENT_PASSWORD_INVALID');
    }

    public static function sameAsCurrent(): RuleViolationException
    {
        return new RuleViolationException('PASSWORD_SAME_AS_CURRENT');
    }
}
