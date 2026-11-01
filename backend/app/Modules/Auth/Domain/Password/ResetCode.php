<?php

declare(strict_types=1);

// Código de recuperación de contraseña que llega por correo: 6 dígitos al azar, con ceros a la izquierda si toca.
// Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Domain\Password;

final class ResetCode
{
    public const LENGTH = 6;

    public static function generate(): string
    {
        return str_pad((string) random_int(0, 10 ** self::LENGTH - 1), self::LENGTH, '0', STR_PAD_LEFT);
    }

    // Pase de un solo uso que reemplaza al código después de verificarlo: largo y al azar
    public static function ticket(): string
    {
        return bin2hex(random_bytes(32));
    }
}
