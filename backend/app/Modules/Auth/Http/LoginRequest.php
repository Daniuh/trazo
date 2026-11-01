<?php

declare(strict_types=1);

// Validación de entrada del login. Solo forma y tamaño: la política de contraseñas aplica al crearlas, no al ingresar.
// Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Http;

use Illuminate\Foundation\Http\FormRequest;

final class LoginRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:191'],
            'password' => ['required', 'string', 'max:255'],
        ];
    }

    public function email(): string
    {
        return $this->string('email')->toString();
    }

    public function password(): string
    {
        return $this->string('password')->toString();
    }
}
