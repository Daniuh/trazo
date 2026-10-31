<?php

declare(strict_types=1);

// Resultado de iniciar sesión: el token en claro (sale una sola vez) y hasta cuándo vale como máximo.
// Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Application;

use DateTimeImmutable;

final readonly class IssuedSession
{
    public function __construct(
        public int $userId,
        public string $token,
        public DateTimeImmutable $expiresAt,
    ) {}
}
