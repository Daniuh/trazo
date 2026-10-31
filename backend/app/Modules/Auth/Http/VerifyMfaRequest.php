<?php

declare(strict_types=1);

// Validación del segundo paso del ingreso: un código de la aplicación (6 dígitos) o uno de recuperación, no ambos.
// Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Http;

use Illuminate\Foundation\Http\FormRequest;

final class VerifyMfaRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'code' => ['required_without:recovery_code', 'prohibits:recovery_code', 'string', 'digits:6'],
            'recovery_code' => ['required_without:code', 'string', 'max:20'],
        ];
    }

    public function code(): ?string
    {
        return $this->filled('code') ? $this->string('code')->toString() : null;
    }

    public function recoveryCode(): ?string
    {
        return $this->filled('recovery_code') ? $this->string('recovery_code')->toString() : null;
    }
}
