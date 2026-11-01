<?php

declare(strict_types=1);

// Resultado de iniciar sesión: el token en claro (sale una sola vez), hasta cuándo vale como máximo y qué paso
// sigue. Si el paso exige MFA, el token solo sirve para verificarlo o configurarlo. Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Application;

use App\Modules\Auth\Domain\Mfa\LoginStep;
use DateTimeImmutable;

final readonly class IssuedSession
{
    public function __construct(
        public int $userId,
        public string $token,
        public DateTimeImmutable $expiresAt,
        public LoginStep $step = LoginStep::Complete,
    ) {}
}
