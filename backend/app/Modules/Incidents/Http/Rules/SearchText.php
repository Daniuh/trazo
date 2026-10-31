<?php

declare(strict_types=1);

// La búsqueda necesita al menos una palabra de 3 letras (o un código como TZ-26-0142 o 26-142): el índice FULLTEXT
// ignora lo más corto. Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Http\Rules;

use App\Modules\Incidents\Domain\IncidentCode;
use App\Modules\Shared\Http\Errors\HasErrorCode;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class SearchText implements HasErrorCode, ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $text = (string) $value;

        if (! IncidentCode::looksLike($text) && preg_match('/[\p{L}\p{N}]{3,}/u', $text) !== 1) {
            $fail($this->errorCode());
        }
    }

    public function errorCode(): string
    {
        return 'SEARCH_TOO_SHORT';
    }
}
