<?php

declare(strict_types=1);

// Validación del cambio de contraseña desde Mi cuenta: la actual y la nueva con su política.
// Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Http;

use App\Modules\Auth\Http\Rules\NewPassword;
use Illuminate\Foundation\Http\FormRequest;

final class ChangePasswordRequest extends FormRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string', 'max:255'],
            'password' => NewPassword::rules($this->personalData()),
        ];
    }

    /**
     * Correo y nombre de quien cambia su contraseña, para que no los use en ella.
     *
     * @return list<string>
     */
    private function personalData(): array
    {
        $user = $this->user();

        return $user === null ? [] : [(string) $user->getAttribute('email'), (string) $user->getAttribute('name')];
    }

    public function currentPassword(): string
    {
        return $this->string('current_password')->toString();
    }

    public function newPassword(): string
    {
        return $this->string('password')->toString();
    }
}
