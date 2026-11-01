<?php

declare(strict_types=1);

// Forma del id de una conexión de Reverb: dos números separados por un punto ("123.456"). Otro valor no se firma.
// Documentado en docs/backend/realtime.md

namespace App\Modules\Realtime\Http\Rules;

use App\Modules\Shared\Http\Errors\HasErrorCode;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class SocketId implements HasErrorCode, ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || preg_match('/^\d{1,20}\.\d{1,20}$/', $value) !== 1) {
            $fail($this->errorCode());
        }
    }

    public function errorCode(): string
    {
        return 'INVALID_IDENTIFIER';
    }
}
