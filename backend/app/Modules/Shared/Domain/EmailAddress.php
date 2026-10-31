<?php

declare(strict_types=1);

// Regla única de normalización de correos: sin espacios y en minúsculas. La usan el modelo y el login.
// Documentado en docs/backend/shared.md

namespace App\Modules\Shared\Domain;

final class EmailAddress
{
    public static function normalize(string $email): string
    {
        return mb_strtolower(trim($email));
    }
}
