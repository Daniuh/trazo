<?php

declare(strict_types=1);

// Motivo por el que una contraseña se considera fácil de adivinar, con el código de error que ve el front (ADR-0070).
// Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Domain\Password;

enum PasswordWeakness: string
{
    case Repetitive = 'PASSWORD_REPETITIVE';
    case Sequential = 'PASSWORD_SEQUENTIAL';
    case Personal = 'PASSWORD_PERSONAL';
}
