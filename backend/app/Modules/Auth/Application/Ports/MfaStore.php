<?php

declare(strict_types=1);

// El MFA de cada persona: su factor TOTP y sus códigos de recuperación. Los códigos llegan en claro y se guardan
// solo con hash. Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Application\Ports;

use App\Modules\Auth\Application\Mfa\MfaFactor;

interface MfaStore
{
    public function find(int $userId): ?MfaFactor;

    // Guarda un secreto nuevo sin confirmar; reemplaza uno pendiente anterior
    public function startSetup(int $userId, string $secret): void;

    /**
     * Confirma el factor y deja estos códigos de recuperación como los únicos válidos, en una sola transacción.
     *
     * @param  list<string>  $recoveryCodes
     */
    public function activate(int $userId, int $step, array $recoveryCodes): void;

    /**
     * Anota el código usado solo si su paso es posterior al último; false si otro pedido ya lo usó (sin repetición).
     */
    public function recordUse(int $userId, int $step): bool;

    // Marca el código como usado; false si no existe o ya se había usado
    public function consumeRecoveryCode(int $userId, string $code): bool;

    /**
     * @param  list<string>  $recoveryCodes
     */
    public function replaceRecoveryCodes(int $userId, array $recoveryCodes): void;

    public function remainingRecoveryCodes(int $userId): int;

    // Quita el factor y sus códigos de recuperación
    public function remove(int $userId): void;
}
