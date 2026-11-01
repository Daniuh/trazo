<?php

declare(strict_types=1);

// Base de las validaciones para crear y editar un rol: nombre, nivel único entre 1 y 99, permisos existentes, política
// de MFA y código de quien administra. El código del rol solo se elige al crear (CreateRoleRequest); al editar no
// cambia (UpdateRoleRequest). Revisa el permiso antes de validar. Documentado en docs/backend/users.md

namespace App\Modules\Users\Http\Requests;

use App\Modules\Users\Application\Data\RoleDraft;
use App\Modules\Users\Domain\MfaPolicy;
use App\Modules\Users\Domain\RoleAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

abstract class RoleRequest extends FormRequest
{
    use ChecksPermission;

    /**
     * Reglas del código del rol: elegirlo al crear, no tocarlo al editar.
     *
     * @return array<string, list<mixed>>
     */
    abstract protected function codeRules(): array;

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
        return [
            ...$this->codeRules(),
            'name' => ['required', 'string', 'min:2', 'max:80'],
            'level' => ['required', 'integer', 'between:1,'.RoleAccess::MAX_LEVEL, $this->uniqueLevel()],
            'permissions' => ['present', 'array', 'max:200'],
            'permissions.*' => ['string', 'distinct', Rule::exists('permissions', 'code')],
            'mfa_required' => ['required', 'boolean'],
            'grace_logins' => ['required', 'integer', 'between:0,'.MfaPolicy::MAX_GRACE_LOGINS],
            'mfa_code' => ['nullable', 'string', 'digits:6'],
        ];
    }

    // Al editar, el propio rol no cuenta como repetido
    protected function uniqueLevel(): Unique
    {
        return Rule::unique('roles', 'level');
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
