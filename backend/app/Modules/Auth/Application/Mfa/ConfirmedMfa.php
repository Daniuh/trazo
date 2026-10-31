<?php

declare(strict_types=1);

// MFA recién activado: los códigos de recuperación en claro (se muestran una sola vez) y, si se activó durante un
// ingreso a medias, la sesión completa que lo reemplaza. Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Application\Mfa;

use App\Modules\Auth\Application\IssuedSession;

final readonly class ConfirmedMfa
{
    /**
     * @param  list<string>  $recoveryCodes
     */
    public function __construct(
        public array $recoveryCodes,
        public ?IssuedSession $session,
    ) {}
}
