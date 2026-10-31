<?php

declare(strict_types=1);

// Emisión y revocación de los tokens de sesión.
// Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Application\Ports;

use App\Modules\Auth\Application\IssuedSession;

interface SessionTokens
{
    public function issue(int $userId): IssuedSession;

    public function revoke(int $tokenId): void;
}
