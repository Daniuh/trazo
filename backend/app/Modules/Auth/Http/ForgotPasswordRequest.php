<?php

declare(strict_types=1);

// Validación de la solicitud de un código de recuperación: solo el correo.
// Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Http;

use Illuminate\Foundation\Http\FormRequest;

final class ForgotPasswordRequest extends FormRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return ['email' => ['required', 'string', 'email', 'max:191']];
    }

    public function email(): string
    {
        return $this->string('email')->toString();
    }
}
