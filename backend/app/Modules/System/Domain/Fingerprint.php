<?php

declare(strict_types=1);

// Huella de un evento: el tipo y su origen. La misma falla cae siempre en la misma fila y suma al contador.
// Documentado en docs/backend/system.md

namespace App\Modules\System\Domain;

final class Fingerprint
{
    public static function of(string $type, string $source): string
    {
        return hash('sha256', $type.'|'.$source);
    }
}
