<?php

declare(strict_types=1);

// Caso de uso: empezar a configurar el MFA. Genera un secreto nuevo que no vale hasta que se confirma con un código.
// Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Application\Mfa;

use App\Modules\Auth\Application\Ports\MfaStore;
use App\Modules\Auth\Application\Ports\OneTimePasswords;
use App\Modules\Auth\Domain\Exceptions\MfaExceptions;
use App\Modules\Shared\Domain\Exceptions\ConflictException;

final readonly class StartMfaSetup
{
    public function __construct(
        private MfaStore $store,
        private OneTimePasswords $otp,
    ) {}

    /**
     * @throws ConflictException si ya tiene MFA
     */
    public function handle(int $userId, string $account): MfaSetup
    {
        if ($this->store->find($userId)?->confirmed === true) {
            throw MfaExceptions::alreadyEnabled();
        }

        $secret = $this->otp->newSecret();
        $this->store->startSetup($userId, $secret);

        return new MfaSetup($secret, $this->otp->provisioningUri($account, $secret));
    }
}
