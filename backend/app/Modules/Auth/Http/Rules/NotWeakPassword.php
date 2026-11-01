<?php

declare(strict_types=1);

// La contraseña nueva no puede ser repetitiva, secuencial ni contener datos de la persona o del producto (ADR-0070).
// Cada motivo tiene su propio código de error. Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Http\Rules;

use App\Modules\Auth\Domain\Password\PasswordWeakness;
use App\Modules\Auth\Domain\Password\WeakPasswordPatterns;
use App\Modules\Shared\Http\Errors\HasErrorCodes;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final readonly class NotWeakPassword implements HasErrorCodes, ValidationRule
{
    /**
     * @param  list<string>  $personal  datos de la persona que no deben aparecer en la contraseña
     */
    public function __construct(private array $personal = []) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $weakness = WeakPasswordPatterns::weakness((string) $value, $this->personal);
        if ($weakness !== null) {
            $fail($weakness->value);
        }
    }

    public static function errorCodes(): array
    {
        return array_map(fn (PasswordWeakness $weakness): string => $weakness->value, PasswordWeakness::cases());
    }
}
