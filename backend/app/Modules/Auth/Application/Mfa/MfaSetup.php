<?php

declare(strict_types=1);

// Datos para configurar la aplicación de autenticación: la clave (para escribirla a mano) y la dirección del QR.
// Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Application\Mfa;

final readonly class MfaSetup
{
    public function __construct(
        public string $secret,
        public string $uri,
    ) {}
}
