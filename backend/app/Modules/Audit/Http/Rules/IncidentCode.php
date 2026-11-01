<?php

declare(strict_types=1);

// Código de un incidente escrito por una persona: "TZ-26-0142", "tz26-142" o "26-142". Se normaliza a TZ-26-0142;
// el año es obligatorio porque el número se repite cada año. Documentado en docs/backend/audit.md

namespace App\Modules\Audit\Http\Rules;

use App\Modules\Incidents\Domain\IncidentCode as Code;
use App\Modules\Shared\Http\Errors\HasErrorCode;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class IncidentCode implements HasErrorCode, ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (self::normalize($value) === null) {
            $fail($this->errorCode());
        }
    }

    public static function normalize(mixed $value): ?string
    {
        return is_string($value) ? Code::normalize($value, (string) config('trazo.incidents.code_prefix')) : null;
    }

    public function errorCode(): string
    {
        return 'INCIDENT_CODE_FORMAT';
    }
}
