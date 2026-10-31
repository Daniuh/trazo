<?php

declare(strict_types=1);

// Reglas de validación propias que exponen su código de error en lugar del nombre de la clase.

namespace App\Modules\Shared\Http\Errors;

interface HasErrorCode
{
    public function errorCode(): string;
}
