<?php

declare(strict_types=1);

// Emisión y revocación de los tokens de sesión. Hay dos clases: el de sesión completa y el de un ingreso a medias,
// que solo sirve para verificar o configurar el MFA y vence en pocos minutos. Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Application\Ports;

use App\Modules\Auth\Application\IssuedSession;
use App\Modules\Auth\Domain\Mfa\LoginStep;

interface SessionTokens
{
    public function issue(int $userId, LoginStep $step = LoginStep::Complete): IssuedSession;

    public function issueChallenge(int $userId, LoginStep $step): IssuedSession;

    public function revoke(int $tokenId): void;

    // Cierra todas las sesiones de la persona, salvo la indicada (la de quien hace el cambio)
    public function revokeAll(int $userId, ?int $exceptTokenId = null): void;
}
