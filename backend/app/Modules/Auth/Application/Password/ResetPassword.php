<?php

declare(strict_types=1);

// Caso de uso: elegir la contraseña nueva con el pase que entregó el código del correo. Si la cuenta tiene MFA, pide
// además el código de la aplicación o uno de recuperación. Al terminar cierra todas las sesiones, levanta el bloqueo
// del login y avisa por correo. Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Application\Password;

use App\Modules\Auth\Application\Mfa\SecurityEvent;
use App\Modules\Auth\Application\Mfa\TotpCheck;
use App\Modules\Auth\Application\Ports\MfaStore;
use App\Modules\Auth\Application\Ports\PasswordResets;
use App\Modules\Auth\Application\Ports\SecurityNotifier;
use App\Modules\Auth\Application\Ports\SessionTokens;
use App\Modules\Auth\Application\Ports\UserAccounts;
use App\Modules\Auth\Domain\Exceptions\MfaExceptions;
use App\Modules\Auth\Domain\Exceptions\PasswordExceptions;
use App\Modules\Auth\Domain\LoginAttemptStore;
use App\Modules\Shared\Application\Ports\SessionSignals;
use App\Modules\Shared\Domain\EmailAddress;
use App\Modules\Shared\Domain\Exceptions\DomainException;
use Carbon\CarbonImmutable;
use Psr\Log\LoggerInterface;

final readonly class ResetPassword
{
    public function __construct(
        private UserAccounts $accounts,
        private PasswordResets $resets,
        private MfaStore $mfa,
        private TotpCheck $totp,
        private SessionTokens $tokens,
        private LoginAttemptStore $loginAttempts,
        private SecurityNotifier $notifier,
        private SessionSignals $signals,
        private LoggerInterface $logger,
        private int $maxAttempts,
    ) {}

    /**
     * @throws DomainException
     */
    public function handle(string $email, string $ticket, string $password, ?string $mfaCode, ?string $recoveryCode): void
    {
        $account = $this->accounts->findActiveByEmail($email);
        $reset = $account === null ? null : $this->resets->current($account->id);

        if ($account === null || $reset === null || ! $reset->ticketIsUsable(CarbonImmutable::now())
            || ! $this->resets->matchesTicket($reset, $ticket)) {
            throw PasswordExceptions::resetExpired();
        }

        $this->checkSecondFactor($account, $reset, $mfaCode, $recoveryCode);

        $this->accounts->updatePassword($account->id, $password);
        $this->resets->close($reset);
        $this->tokens->revokeAll($account->id);
        $this->signals->changed($account->id);
        // Quien recuperó su contraseña puede entrar de inmediato, aunque antes la hubiera escrito mal varias veces
        $this->loginAttempts->clear(hash('sha256', EmailAddress::normalize($email)));
        $this->notifier->notify($account->id, SecurityEvent::PasswordChanged);
        $this->logger->info('Contraseña recuperada por correo', ['user_id' => $account->id]);
    }

    /**
     * Con MFA, la recuperación exige también el segundo factor: un código de la aplicación o uno de recuperación.
     *
     * @throws DomainException
     */
    private function checkSecondFactor(Account $account, PasswordReset $reset, ?string $mfaCode, ?string $recoveryCode): void
    {
        $factor = $this->mfa->find($account->id);
        if ($factor === null || ! $factor->confirmed) {
            return;
        }

        if ($mfaCode === null && $recoveryCode === null) {
            throw MfaExceptions::codeRequired();
        }

        $valid = $recoveryCode !== null
            ? $this->mfa->consumeRecoveryCode($account->id, $recoveryCode)
            : $this->totp->consume($account->id, $factor, (string) $mfaCode);

        if ($valid) {
            if ($recoveryCode !== null) {
                $this->notifier->notify($account->id, SecurityEvent::RecoveryCodeUsed);
            }

            return;
        }

        // Los fallos del segundo factor también gastan los intentos de la solicitud
        $left = $this->maxAttempts - $this->resets->recordFailure($reset);
        if ($left <= 0) {
            $this->resets->close($reset);
            throw PasswordExceptions::resetExpired();
        }

        throw $recoveryCode !== null ? MfaExceptions::invalidRecoveryCode($left) : MfaExceptions::invalidCode($left);
    }
}
