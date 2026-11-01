<?php

declare(strict_types=1);

// Puente con el módulo de sesión: cierra sesiones, quita el MFA y confirma la identidad de quien administra con el
// código de su aplicación (sin repetición, como en el ingreso). Cinco códigos incorrectos en un minuto pausan la
// reautenticación. Documentado en docs/backend/users.md

namespace App\Modules\Users\Infrastructure;

use App\Modules\Auth\Application\Mfa\TotpCheck;
use App\Modules\Auth\Application\Ports\MfaStore;
use App\Modules\Auth\Application\Ports\SessionTokens;
use App\Modules\Auth\Domain\Exceptions\MfaExceptions;
use App\Modules\Shared\Domain\Exceptions\RuleViolationException;
use App\Modules\Shared\Domain\Exceptions\TemporarilyLockedException;
use App\Modules\Users\Application\Ports\AccountSecurity;
use Illuminate\Support\Facades\RateLimiter;

final readonly class AuthAccountSecurity implements AccountSecurity
{
    private const MAX_FAILURES = 5;

    private const DECAY_SECONDS = 60;

    public function __construct(
        private SessionTokens $sessions,
        private MfaStore $mfa,
        private TotpCheck $totp,
    ) {}

    public function revokeSessions(int $userId): void
    {
        $this->sessions->revokeAll($userId);
    }

    public function removeMfa(int $userId): void
    {
        $this->mfa->remove($userId);
    }

    public function confirmIdentity(int $adminId, ?string $code): void
    {
        $key = "reauth:{$adminId}";
        if (RateLimiter::tooManyAttempts($key, self::MAX_FAILURES)) {
            throw new TemporarilyLockedException('REAUTHENTICATION_LOCKED', RateLimiter::availableIn($key));
        }

        if ($code === null || $code === '') {
            throw new RuleViolationException('REAUTHENTICATION_REQUIRED');
        }

        // Sin MFA no hay con qué confirmar: primero debe configurarlo
        $factor = $this->mfa->find($adminId);
        if ($factor === null || ! $factor->confirmed) {
            throw new RuleViolationException('REAUTHENTICATION_UNAVAILABLE');
        }

        if (! $this->totp->consume($adminId, $factor, $code)) {
            RateLimiter::hit($key, self::DECAY_SECONDS);

            throw MfaExceptions::invalidCode(max(0, self::MAX_FAILURES - (int) RateLimiter::attempts($key)));
        }

        RateLimiter::clear($key);
    }
}
