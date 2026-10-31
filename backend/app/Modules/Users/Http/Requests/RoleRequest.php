<?php

declare(strict_types=1);

// Validación para crear y editar un rol: código (solo al crear, único y en minúsculas), nombre, nivel único entre 1 y
// 99, permisos existentes y política de MFA. Revisa el permiso antes de validar. Documentado en docs/backend/users.md

namespace App\Modules\Users\Http\Requests;

use App\Modules\Users\Application\Data\RoleDraft;
use App\Modules\Users\Domain\MfaPolicy;
use App\Modules\Users\Domain\RoleAccess;
use App\Modules\Users\Http\Rules\RoleCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class RoleRequest extends FormRequest
{
    use ChecksPermission;

    protected function requiredPermission(): string
    {
        return RoleAccess::PERMISSION;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('name'))) {
            $this->merge(['name' => $this->string('name')->squish()->toString()]);
        }
        if (is_string($this->input('code'))) {
            $this->merge(['code' => $this->string('code')->trim()->lower()->toString()]);
        }
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $creating = $this->isMethod('POST');
        $level = Rule::unique('roles', 'level');
        $current = $this->route('role');
        if (! $creating && is_string($current)) {
            $level->where(fn ($query) => $query->where('code', '!=', $current));
        }

        return [
            'code' => $creating
                ? ['required', 'string', 'min:3', 'max:40', new RoleCode, Rule::unique('roles', 'code')]
                : ['prohibited'],
            'name' => ['required', 'string', 'min:2', 'max:80'],
            'level' => ['required', 'integer', 'between:1,'.RoleAccess::MAX_LEVEL, $level],
            'permissions' => ['present', 'array', 'max:200'],
            'permissions.*' => ['string', 'distinct', Rule::exists('permissions', 'code')],
            'mfa_required' => ['required', 'boolean'],
            'grace_logins' => ['required', 'integer', 'between:0,'.MfaPolicy::MAX_GRACE_LOGINS],
            'mfa_code' => ['nullable', 'string', 'digits:6'],
        ];
    }

    public function code(): string
    {
        return $this->string('code')->toString();
    }

    public function draft(): RoleDraft
    {
        return new RoleDraft(
            $this->string('name')->toString(),
            $this->integer('level'),
            array_values(array_map('strval', (array) $this->input('permissions', []))),
            new MfaPolicy($this->boolean('mfa_required'), $this->integer('grace_logins')),
        );
    }

    public function mfaCode(): ?string
    {
        return $this->filled('mfa_code') ? $this->string('mfa_code')->toString() : null;
    }
}
