<?php

declare(strict_types=1);

// Caso de uso: generar códigos de recuperación nuevos. Pide un código actual de la aplicación, invalida los
// anteriores y avisa por correo. Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Application\Mfa;

use App\Modules\Auth\Application\Ports\MfaStore;
use App\Modules\Auth\Application\Ports\SecurityNotifier;
use App\Modules\Auth\Domain\Exceptions\MfaExceptions;
use App\Modules\Auth\Domain\Mfa\RecoveryCode;
use App\Modules\Shared\Domain\Exceptions\DomainException;

final readonly class RegenerateRecoveryCodes
{
    public function __construct(
        private MfaStore $store,
        private TotpCheck $check,
        private SecurityNotifier $notifier,
        private int $recoveryCodes,
    ) {}

    /**
     * @return list<string>
     *
     * @throws DomainException
     */
    public function handle(int $userId, string $code): array
    {
        $factor = $this->store->find($userId);
        if ($factor === null || ! $factor->confirmed) {
            throw MfaExceptions::notEnabled();
        }
        if (! $this->check->consume($userId, $factor, $code)) {
            throw MfaExceptions::invalidCode();
        }

        $codes = RecoveryCode::batch($this->recoveryCodes);
        $this->store->replaceRecoveryCodes($userId, $codes);
        $this->notifier->notify($userId, SecurityEvent::RecoveryCodesRegenerated);

        return $codes;
    }
}
