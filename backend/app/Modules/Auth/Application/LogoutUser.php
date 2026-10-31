<?php

declare(strict_types=1);

// Caso de uso: cerrar la sesión actual. El token se revoca en el servidor y deja de servir de inmediato.
// Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Application;

use App\Modules\Auth\Application\Ports\SessionTokens;

final readonly class LogoutUser
{
    public function __construct(private SessionTokens $tokens) {}

    public function handle(int $tokenId): void
    {
        $this->tokens->revoke($tokenId);
    }
}
