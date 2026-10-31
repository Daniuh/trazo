<?php

declare(strict_types=1);

// Caso de uso: confirmar el MFA con el primer código de la aplicación. Lo activa, entrega los códigos de recuperación
// y avisa por correo. Si la persona lo configuró para poder entrar, le abre la sesión completa.
// Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Application\Mfa;

use App\Modules\Auth\Application\Ports\MfaStore;
use App\Modules\Auth\Application\Ports\OneTimePasswords;
use App\Modules\Auth\Application\Ports\SecurityNotifier;
use App\Modules\Auth\Application\Ports\SessionTokens;
use App\Modules\Auth\Domain\Exceptions\MfaExceptions;
use App\Modules\Auth\Domain\Mfa\RecoveryCode;
use App\Modules\Shared\Domain\Exceptions\DomainException;

final readonly class ConfirmMfaSetup
{
    public function __construct(
        private MfaStore $store,
        private OneTimePasswords $otp,
        private SessionTokens $tokens,
        private SecurityNotifier $notifier,
        private int $recoveryCodes,
    ) {}

    /**
     * @param  int|null  $challengeTokenId  El token del ingreso a medias, si se está configurando para poder entrar
     *
     * @throws DomainException
     */
    public function handle(int $userId, string $code, ?int $challengeTokenId): ConfirmedMfa
    {
        $factor = $this->store->find($userId);
        if ($factor === null) {
            throw MfaExceptions::setupNotStarted();
        }
        if ($factor->confirmed) {
            throw MfaExceptions::alreadyEnabled();
        }

        $step = $this->otp->verify($factor->secret, $code);
        if ($step === null) {
            throw MfaExceptions::invalidCode();
        }

        $codes = RecoveryCode::batch($this->recoveryCodes);
        $this->store->activate($userId, $step, $codes);
        $this->notifier->notify($userId, SecurityEvent::MfaEnabled);

        $session = null;
        if ($challengeTokenId !== null) {
            $this->tokens->revoke($challengeTokenId);
            $session = $this->tokens->issue($userId);
        }

        return new ConfirmedMfa($codes, $session);
    }
}
