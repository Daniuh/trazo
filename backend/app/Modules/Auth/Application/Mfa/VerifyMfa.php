<?php

declare(strict_types=1);

// Caso de uso: segundo paso del ingreso. Con un código de la aplicación o uno de recuperación, cambia el token del
// ingreso a medias por una sesión completa. Tras varios intentos fallidos revoca el token y hay que empezar de nuevo.
// Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Application\Mfa;

use App\Modules\Auth\Application\IssuedSession;
use App\Modules\Auth\Application\Ports\MfaStore;
use App\Modules\Auth\Application\Ports\SecurityNotifier;
use App\Modules\Auth\Application\Ports\SessionTokens;
use App\Modules\Auth\Domain\Exceptions\MfaExceptions;
use App\Modules\Auth\Domain\LoginAttemptStore;
use App\Modules\Shared\Application\Ports\SystemEvents;
use App\Modules\Shared\Application\SystemEventType;
use App\Modules\Shared\Domain\Exceptions\DomainException;
use Psr\Log\LoggerInterface;

final readonly class VerifyMfa
{
    public function __construct(
        private MfaStore $store,
        private TotpCheck $check,
        private SessionTokens $tokens,
        private LoginAttemptStore $attempts,
        private SecurityNotifier $notifier,
        private LoggerInterface $logger,
        private SystemEvents $events,
        private int $maxAttempts,
    ) {}

    /**
     * @throws DomainException
     */
    public function handle(int $userId, int $challengeTokenId, ?string $code, ?string $recoveryCode): IssuedSession
    {
        $factor = $this->store->find($userId);
        if ($factor === null || ! $factor->confirmed) {
            throw MfaExceptions::notEnabled();
        }

        // Los intentos se cuentan por ingreso: un token nuevo (otra contraseña correcta) empieza de cero
        $key = 'mfa:'.$challengeTokenId;

        if ($recoveryCode !== null) {
            if (! $this->store->consumeRecoveryCode($userId, $recoveryCode)) {
                $this->fail($key, $userId, $challengeTokenId, true);
            }
            $this->notifier->notify($userId, SecurityEvent::RecoveryCodeUsed);
            $this->logger->warning('Ingreso con un código de recuperación', [
                'user_id' => $userId,
                'codes_left' => $this->store->remainingRecoveryCodes($userId),
            ]);
        } elseif (! $this->check->consume($userId, $factor, (string) $code)) {
            $this->fail($key, $userId, $challengeTokenId, false);
        }

        $this->attempts->clear($key);
        $this->tokens->revoke($challengeTokenId);

        return $this->tokens->issue($userId);
    }

    private function fail(string $key, int $userId, int $challengeTokenId, bool $recovery): never
    {
        $left = $this->maxAttempts - $this->attempts->recordFailure($key);

        if ($left <= 0) {
            $this->attempts->clear($key);
            $this->tokens->revoke($challengeTokenId);
            $this->logger->warning('Ingreso con MFA cortado por intentos fallidos', ['user_id' => $userId]);
            $this->events->record(SystemEventType::MfaAttemptsExceeded, "user:{$userId}", 'Ingreso cortado tras varios códigos de verificación incorrectos', [
                'user_id' => $userId,
                'with_recovery_code' => $recovery,
            ]);

            throw MfaExceptions::tooManyAttempts();
        }

        throw $recovery ? MfaExceptions::invalidRecoveryCode($left) : MfaExceptions::invalidCode($left);
    }
}
