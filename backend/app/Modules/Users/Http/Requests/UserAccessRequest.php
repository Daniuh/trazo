<?php

declare(strict_types=1);

// Validación de los cambios de acceso de una persona: su rol, sus permisos o restablecer su MFA o su acceso. Todos
// aceptan el código de quien administra, que el caso de uso exige cuando el cambio es sensible.
// Documentado en docs/backend/users.md

namespace App\Modules\Users\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UserAccessRequest extends FormRequest
{
    use ChecksPermission;

    protected function requiredPermission(): string
    {
        return 'users.view';
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $rules = ['mfa_code' => ['nullable', 'string', 'digits:6']];

        return match ($this->route()?->getName()) {
            'users.role' => [...$rules, 'role' => ['required', 'string', Rule::exists('roles', 'code')]],
            'users.permissions' => [
                ...$rules,
                'permissions' => ['present', 'array', 'max:200'],
                'permissions.*' => ['string', 'distinct', Rule::exists('permissions', 'code')],
            ],
            default => $rules,
        };
    }

    public function role(): string
    {
        return $this->string('role')->toString();
    }

    /**
     * @return list<string>
     */
    public function permissions(): array
    {
        return array_values(array_map('strval', (array) $this->input('permissions', [])));
    }

    public function mfaCode(): ?string
    {
        return $this->filled('mfa_code') ? $this->string('mfa_code')->toString() : null;
    }
}
