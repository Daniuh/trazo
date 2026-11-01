<?php

declare(strict_types=1);

// Validación de la contraseña nueva al recuperar: el pase, la política de la contraseña y, si aplica, el segundo
// factor (código de la aplicación o de recuperación, no ambos). Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Http;

use App\Modules\Auth\Http\Rules\NewPassword;
use Illuminate\Foundation\Http\FormRequest;

final class ResetPasswordRequest extends FormRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:191'],
            'ticket' => ['required', 'string', 'size:64'],
            // Solo el correo que ya trae la petición: buscar el nombre antes de validar el pase revelaría datos de la cuenta
            'password' => NewPassword::rules([$this->string('email')->toString()]),
            'mfa_code' => ['nullable', 'string', 'digits:6', 'prohibits:recovery_code'],
            'recovery_code' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function email(): string
    {
        return $this->string('email')->toString();
    }

    public function ticket(): string
    {
        return $this->string('ticket')->toString();
    }

    public function newPassword(): string
    {
        return $this->string('password')->toString();
    }

    public function mfaCode(): ?string
    {
        return $this->filled('mfa_code') ? $this->string('mfa_code')->toString() : null;
    }

    public function recoveryCode(): ?string
    {
        return $this->filled('recovery_code') ? $this->string('recovery_code')->toString() : null;
    }
}
