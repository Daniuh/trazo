<?php

declare(strict_types=1);

// Validación de los cambios de acceso de una persona que solo llevan el código de quien administra: activar,
// desactivar y restablecer su MFA o su acceso. Cambiar su rol o sus permisos suma sus campos (ChangeRoleRequest y
// ChangePermissionsRequest). El caso de uso exige el código cuando el cambio es sensible. Documentado en docs/backend/users.md

namespace App\Modules\Users\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserAccessRequest extends FormRequest
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
        return ['mfa_code' => ['nullable', 'string', 'digits:6']];
    }

    public function mfaCode(): ?string
    {
        return $this->filled('mfa_code') ? $this->string('mfa_code')->toString() : null;
    }
}
