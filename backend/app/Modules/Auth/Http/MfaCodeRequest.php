<?php

declare(strict_types=1);

// Validación de un código de la aplicación de autenticación (6 dígitos). Lo usan confirmar, regenerar y desactivar.
// Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Http;

use Illuminate\Foundation\Http\FormRequest;

final class MfaCodeRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'digits:6'],
        ];
    }

    public function code(): string
    {
        return $this->string('code')->toString();
    }
}
