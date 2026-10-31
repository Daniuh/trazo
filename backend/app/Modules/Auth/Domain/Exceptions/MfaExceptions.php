<?php

declare(strict_types=1);

// Errores del MFA, cada uno con su código estable para el catálogo de mensajes del front.
// Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Domain\Exceptions;

use App\Modules\Shared\Domain\Exceptions\AuthenticationFailedException;
use App\Modules\Shared\Domain\Exceptions\ConflictException;
use App\Modules\Shared\Domain\Exceptions\RuleViolationException;

final class MfaExceptions
{
    // Con los intentos que quedan cuando se está ingresando; sin ellos en las acciones de una sesión ya abierta
    public static function invalidCode(?int $attemptsLeft = null): RuleViolationException
    {
        return new RuleViolationException('MFA_CODE_INVALID', $attemptsLeft === null ? [] : ['attempts_left' => $attemptsLeft]);
    }

    // La cuenta tiene MFA y no llegó ni el código de la aplicación ni uno de recuperación
    public static function codeRequired(): RuleViolationException
    {
        return new RuleViolationException('MFA_CODE_REQUIRED');
    }

    // Era un código correcto, pero ya se usó: hay que esperar el siguiente
    public static function codeAlreadyUsed(): RuleViolationException
    {
        return new RuleViolationException('MFA_CODE_ALREADY_USED');
    }

    public static function invalidRecoveryCode(int $attemptsLeft): RuleViolationException
    {
        return new RuleViolationException('MFA_RECOVERY_CODE_INVALID', ['attempts_left' => $attemptsLeft]);
    }

    // Se agotaron los intentos de este ingreso: el token se revocó y hay que volver a poner la contraseña
    public static function tooManyAttempts(): AuthenticationFailedException
    {
        return new AuthenticationFailedException('MFA_TOO_MANY_ATTEMPTS');
    }

    public static function alreadyEnabled(): ConflictException
    {
        return new ConflictException('MFA_ALREADY_ENABLED');
    }

    public static function notEnabled(): ConflictException
    {
        return new ConflictException('MFA_NOT_ENABLED');
    }

    public static function setupNotStarted(): ConflictException
    {
        return new ConflictException('MFA_SETUP_NOT_STARTED');
    }

    public static function requiredByPolicy(): ConflictException
    {
        return new ConflictException('MFA_REQUIRED_BY_POLICY');
    }

    // Se pidió verificar un código con una sesión que ya está completa
    public static function notPending(): ConflictException
    {
        return new ConflictException('MFA_NOT_PENDING');
    }
}
