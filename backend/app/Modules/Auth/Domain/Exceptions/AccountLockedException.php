<?php

declare(strict_types=1);

// Demasiados intentos fallidos seguidos: hay que esperar. Se responde igual exista o no el correo.
// Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Domain\Exceptions;

use App\Modules\Shared\Domain\Exceptions\TemporarilyLockedException;

final class AccountLockedException extends TemporarilyLockedException
{
    public function __construct(int $retryAfterSeconds)
    {
        parent::__construct('ACCOUNT_LOCKED', $retryAfterSeconds);
    }
}
