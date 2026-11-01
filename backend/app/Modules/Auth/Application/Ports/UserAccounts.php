<?php

declare(strict_types=1);

// Las cuentas de las personas, para recuperar y cambiar la contraseña. La contraseña se guarda siempre con Argon2id.
// Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Application\Ports;

use App\Modules\Auth\Application\Password\Account;

interface UserAccounts
{
    // Solo cuentas activas: una desactivada no puede recuperar su contraseña
    public function findActiveByEmail(string $email): ?Account;

    public function find(int $userId): Account;

    public function passwordMatches(int $userId, string $password): bool;

    public function updatePassword(int $userId, string $password): void;
}
