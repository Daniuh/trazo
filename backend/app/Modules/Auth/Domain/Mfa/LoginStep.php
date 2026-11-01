<?php

declare(strict_types=1);

// Qué sigue después de una contraseña correcta. Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Domain\Mfa;

enum LoginStep: string
{
    // Sesión completa: no tiene MFA y su política no lo exige
    case Complete = 'complete';

    // Sesión completa, pero se le sugiere configurar el MFA: está usando un ingreso de gracia
    case SetupSuggested = 'setup_suggested';

    // Tiene MFA: falta el código
    case Verify = 'mfa_verify';

    // Su política lo exige y ya no le quedan ingresos de gracia: debe configurarlo para entrar
    case Setup = 'mfa_setup';

    public function grantsSession(): bool
    {
        return $this === self::Complete || $this === self::SetupSuggested;
    }
}
