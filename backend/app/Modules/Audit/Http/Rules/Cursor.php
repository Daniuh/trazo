<?php

declare(strict_types=1);

// El cursor de "cargar más" debe ser uno que entregó la API; uno alterado a mano no se acepta.
// Documentado en docs/backend/audit.md

namespace App\Modules\Audit\Http\Rules;

use App\Modules\Audit\Domain\AuditCursor;
use App\Modules\Shared\Http\Errors\HasErrorCode;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class Cursor implements HasErrorCode, ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || AuditCursor::decode($value) === null) {
            $fail($this->errorCode());
        }
    }

    public function errorCode(): string
    {
        return 'INVALID_CURSOR';
    }
}
