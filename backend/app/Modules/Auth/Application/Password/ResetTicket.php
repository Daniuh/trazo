<?php

declare(strict_types=1);

// Resultado de verificar el código del correo: el pase para elegir la contraseña nueva y si la cuenta pide además
// el código del MFA. Saberlo no revela nada: ya se demostró que el correo es de la persona.
// Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Application\Password;

final readonly class ResetTicket
{
    public function __construct(
        public string $ticket,
        public bool $mfaRequired,
    ) {}
}
