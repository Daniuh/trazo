<?php

declare(strict_types=1);

// Caso de uso: iniciar sesión con correo y contraseña, aplicando el bloqueo progresivo.
// Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Application;

use App\Modules\Auth\Application\Ports\CredentialVerifier;
use App\Modules\Auth\Application\Ports\SessionTokens;
use App\Modules\Auth\Domain\Exceptions\AccountLockedException;
use App\Modules\Auth\Domain\Exceptions\InvalidCredentialsException;
use App\Modules\Auth\Domain\LockoutPolicy;
use App\Modules\Auth\Domain\LoginAttemptStore;
use App\Modules\Shared\Domain\EmailAddress;
use Psr\Log\LoggerInterface;

final readonly class LoginUser
{
    public function __construct(
        private LoginAttemptStore $attempts,
        private LockoutPolicy $policy,
        private CredentialVerifier $credentials,
        private SessionTokens $tokens,
        private LoggerInterface $logger,
    ) {}

    /**
     * @throws AccountLockedException
     * @throws InvalidCredentialsException
     */
    public function handle(string $email, string $password, string $ip): IssuedSession
    {
        // Se cuenta por huella del correo, exista o no la cuenta: así el bloqueo no revela qué correos existen
        $key = hash('sha256', EmailAddress::normalize($email));

        $lockedFor = $this->attempts->lockedFor($key);
        if ($lockedFor > 0) {
            throw new AccountLockedException($lockedFor);
        }

        $userId = $this->credentials->verify($email, $password);
        if ($userId === null) {
            $this->registerFailure($key, $ip);
        }

        $this->attempts->clear($key);

        return $this->tokens->issue($userId);
    }

    /**
     * @throws AccountLockedException
     * @throws InvalidCredentialsException
     */
    private function registerFailure(string $key, string $ip): never
    {
        $failures = $this->attempts->recordFailure($key);
        $lockSeconds = $this->policy->lockSecondsAfter($failures);

        if ($lockSeconds === 0) {
            throw new InvalidCredentialsException;
        }

        $this->attempts->lock($key, $lockSeconds);
        // Un fallo aislado no se registra; un bloqueo sí, porque puede ser un ataque
        $this->logger->warning('Inicio de sesión bloqueado por intentos fallidos', [
            'email_hash' => $key,
            'ip' => $ip,
            'failures' => $failures,
            'lock_seconds' => $lockSeconds,
        ]);

        throw new AccountLockedException($lockSeconds);
    }
}
