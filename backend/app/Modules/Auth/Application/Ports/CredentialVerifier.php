<?php

declare(strict_types=1);

// Comprueba correo y contraseña. Debe tardar lo mismo exista o no la cuenta.
// Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Application\Ports;

interface CredentialVerifier
{
    /** Id del usuario si las credenciales son correctas y la cuenta está activa; null en cualquier otro caso. */
    public function verify(string $email, string $password): ?int;
}
