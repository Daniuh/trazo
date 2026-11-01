<?php

declare(strict_types=1);

// La contraseña nueva no puede aparecer en filtraciones conocidas ni entre las más comunes (ADR-0016).
// Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Http\Rules;

use App\Modules\Auth\Application\Ports\BreachedPasswords;
use App\Modules\Shared\Http\Errors\HasErrorCode;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final readonly class NotBreachedPassword implements HasErrorCode, ValidationRule
{
    public function __construct(private BreachedPasswords $breached) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->breached->isBreached((string) $value)) {
            $fail($this->errorCode());
        }
    }

    public function errorCode(): string
    {
        return 'PASSWORD_BREACHED';
    }
}
