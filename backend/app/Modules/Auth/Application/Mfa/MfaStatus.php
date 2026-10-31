<?php

declare(strict_types=1);

// Estado del MFA de la persona para su pantalla de seguridad: si lo tiene, si su rol lo exige y cuántos códigos de
// recuperación le quedan. Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Application\Mfa;

final readonly class MfaStatus
{
    public function __construct(
        public bool $enabled,
        public bool $required,
        public int $recoveryCodesLeft,
    ) {}
}
