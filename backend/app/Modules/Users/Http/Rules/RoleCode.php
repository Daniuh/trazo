<?php

declare(strict_types=1);

// Forma del código de un rol: empieza con una letra y sigue con letras minúsculas sin tildes, números o guion bajo
// (p. ej. soporte_n2). Es su clave estable para migraciones e integraciones. Documentado en docs/backend/users.md

namespace App\Modules\Users\Http\Rules;

use App\Modules\Shared\Http\Errors\HasErrorCode;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class RoleCode implements HasErrorCode, ValidationRule
{
    public const PATTERN = '[a-z][a-z0-9_]*';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || preg_match('/^'.self::PATTERN.'$/', $value) !== 1) {
            $fail($this->errorCode());
        }
    }

    public function errorCode(): string
    {
        return 'ROLE_CODE_FORMAT';
    }
}
