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

    // Para registros que lee una persona: "da***@gmail.com". Alcanza para reconocer la cuenta sin exponerla completa
    public static function masked(string $email): string
    {
        $normalized = self::normalize($email);
        $at = mb_strrpos($normalized, '@');
        if ($at === false) {
            return mb_substr($normalized, 0, 2).'***';
        }

        return mb_substr($normalized, 0, min(2, $at)).'***'.mb_substr($normalized, $at);
    }
}
