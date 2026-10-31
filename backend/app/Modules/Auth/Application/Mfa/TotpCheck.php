<?php

declare(strict_types=1);

// Comprueba un código de la aplicación de autenticación y lo deja usado: el mismo código no sirve dos veces,
// aunque lleguen dos pedidos a la vez. Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Application\Mfa;

use App\Modules\Auth\Application\Ports\MfaStore;
use App\Modules\Auth\Application\Ports\OneTimePasswords;
use App\Modules\Auth\Domain\Exceptions\MfaExceptions;
use App\Modules\Shared\Domain\Exceptions\RuleViolationException;

final readonly class TotpCheck
{
    public function __construct(
        private OneTimePasswords $otp,
        private MfaStore $store,
    ) {}

    /**
     * True si el código vale y quedó usado; false si es incorrecto.
     *
     * @throws RuleViolationException si es un código correcto que ya se usó
     */
    public function consume(int $userId, MfaFactor $factor, string $code): bool
    {
        $step = $this->otp->verify($factor->secret, $code, $factor->lastUsedStep);

        if ($step === null) {
            // Distinguir "ya usado" de "incorrecto" le dice a la persona que solo debe esperar el siguiente
            if ($factor->lastUsedStep !== null && $this->otp->verify($factor->secret, $code) !== null) {
                throw MfaExceptions::codeAlreadyUsed();
            }

            return false;
        }

        if (! $this->store->recordUse($userId, $step)) {
            throw MfaExceptions::codeAlreadyUsed();
        }

        return true;
    }
}
