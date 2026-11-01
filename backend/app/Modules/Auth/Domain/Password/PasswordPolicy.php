<?php

declare(strict_types=1);

// Política de contraseñas (ADR-0016): largo mínimo y máximo. Que no esté filtrada lo revisa BreachedPasswords.
// El máximo evita que una contraseña enorme haga trabajar de más a Argon2id. Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Domain\Password;

final class PasswordPolicy
{
    public const MIN_LENGTH = 12;

    public const MAX_LENGTH = 128;
}
