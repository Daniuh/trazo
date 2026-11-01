<?php

declare(strict_types=1);

// Validación del código que llegó al correo: el correo y 6 dígitos.
// Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Http;

use App\Modules\Auth\Domain\Password\ResetCode;
use Illuminate\Foundation\Http\FormRequest;

final class VerifyResetCodeRequest extends FormRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:191'],
            'code' => ['required', 'string', 'digits:'.ResetCode::LENGTH],
        ];
    }

    public function email(): string
    {
        return $this->string('email')->toString();
    }

    public function code(): string
    {
        return $this->string('code')->toString();
    }
}
