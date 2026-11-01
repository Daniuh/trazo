<?php

declare(strict_types=1);

// Reglas de validación propias que pueden fallar con distintos códigos según el motivo: fallan con el código como
// mensaje y declaran aquí todos los posibles, para que el formateador lo reconozca. Documentado en docs/api/errores.md

namespace App\Modules\Shared\Http\Errors;

interface HasErrorCodes
{
    /** @return list<string> */
    public static function errorCodes(): array;
}
