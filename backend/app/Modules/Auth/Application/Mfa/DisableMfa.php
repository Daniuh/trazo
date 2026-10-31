<?php

declare(strict_types=1);

// Caso de uso: desactivar el MFA. Solo si la política de la persona no lo exige, y con un código actual de la
// aplicación. Avisa por correo. Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Application\Mfa;

use App\Modules\Auth\Application\Ports\MfaPolicies;
use App\Modules\Auth\Application\Ports\MfaStore;
use App\Modules\Auth\Application\Ports\SecurityNotifier;
use App\Modules\Auth\Domain\Exceptions\MfaExceptions;
use App\Modules\Shared\Domain\Exceptions\DomainException;

final readonly class DisableMfa
{
    public function __construct(
        private MfaStore $store,
        private MfaPolicies $policies,
        private TotpCheck $check,
        private SecurityNotifier $notifier,
    ) {}

    /**
     * @throws DomainException
     */
    public function handle(int $userId, string $code): void
    {
        if ($this->policies->requirementFor($userId)->required) {
            throw MfaExceptions::requiredByPolicy();
        }

        $factor = $this->store->find($userId);
        if ($factor === null || ! $factor->confirmed) {
            throw MfaExceptions::notEnabled();
        }
        if (! $this->check->consume($userId, $factor, $code)) {
            throw MfaExceptions::invalidCode();
        }

        $this->store->remove($userId);
        $this->notifier->notify($userId, SecurityEvent::MfaDisabled);
    }
}
