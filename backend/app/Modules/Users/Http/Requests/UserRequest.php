<?php

declare(strict_types=1);

// Validación para crear y editar una persona: nombre, correo (normalizado y único) y, al crear, su rol y el código
// de quien administra si el rol es sensible. Documentado en docs/backend/users.md

namespace App\Modules\Users\Http\Requests;

use App\Modules\Shared\Domain\EmailAddress;
use App\Modules\Users\Application\Data\NewUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UserRequest extends FormRequest
{
    use ChecksPermission;

    protected function requiredPermission(): string
    {
        return $this->isMethod('POST') ? 'users.create' : 'users.update';
    }

    // El correo se compara ya normalizado, igual que como se guarda
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => EmailAddress::normalize($this->string('email')->toString())]);
        }
        if (is_string($this->input('name'))) {
            $this->merge(['name' => $this->string('name')->squish()->toString()]);
        }
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $creating = $this->isMethod('POST');
        $unique = Rule::unique('users', 'email');
        $current = $this->route('user');
        if (! $creating && is_string($current)) {
            $unique->where(fn ($query) => $query->where('ulid', '!=', strtolower($current)));
        }

        return [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', 'max:191', $unique],
            'role' => $creating ? ['required', 'string', Rule::exists('roles', 'code')] : ['prohibited'],
            'mfa_code' => $creating ? ['nullable', 'string', 'digits:6'] : ['prohibited'],
        ];
    }

    public function newUser(): NewUser
    {
        return new NewUser($this->name(), $this->email(), $this->string('role')->toString());
    }

    public function name(): string
    {
        return $this->string('name')->toString();
    }

    public function email(): string
    {
        return $this->string('email')->toString();
    }

    public function mfaCode(): ?string
    {
        return $this->filled('mfa_code') ? $this->string('mfa_code')->toString() : null;
    }
}
