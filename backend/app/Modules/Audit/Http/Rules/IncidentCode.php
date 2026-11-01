<?php

declare(strict_types=1);

// Código de un incidente escrito por una persona: "INC-123", "inc-000123" o solo "123". Se normaliza a INC-000123.
// Documentado en docs/backend/audit.md

namespace App\Modules\Audit\Http\Rules;

use App\Modules\Shared\Http\Errors\HasErrorCode;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class IncidentCode implements HasErrorCode, ValidationRule
{
    private const PATTERN = '/^(?:INC-?)?(\d{1,10})$/i';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (self::normalize($value) === null) {
            $fail($this->errorCode());
        }
    }

    public static function normalize(mixed $value): ?string
    {
        if (! is_string($value) || preg_match(self::PATTERN, trim($value), $match) !== 1) {
            return null;
        }

        return sprintf('INC-%06d', (int) $match[1]);
    }

    public function errorCode(): string
    {
        return 'INCIDENT_CODE_FORMAT';
    }
}
