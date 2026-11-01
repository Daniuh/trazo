<?php

declare(strict_types=1);

// Validación para crear y editar una persona: nombres y apellidos, correo (normalizado y único) y, al crear, su rol y el código
// de quien administra si el rol es sensible. Documentado en docs/backend/users.md

namespace App\Modules\Users\Http\Requests;

use App\Modules\Shared\Domain\EmailAddress;
use App\Modules\Users\Application\Data\NewUser;
use App\Modules\Users\Domain\PersonName;
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
        foreach (['given_names', 'family_names'] as $part) {
            if (is_string($this->input($part))) {
                $this->merge([$part => PersonName::clean($this->string($part)->toString())]);
            }
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
            'given_names' => ['required', 'string', 'max:'.PersonName::MAX_PART],
            'family_names' => ['required', 'string', 'max:'.PersonName::MAX_PART],
            // El nombre completo lo arma la base: no se escribe
            'name' => ['prohibited'],
            'email' => ['required', 'string', 'email:rfc', 'max:191', $unique],
            'role' => $creating ? ['required', 'string', Rule::exists('roles', 'code')] : ['prohibited'],
            'mfa_code' => $creating ? ['nullable', 'string', 'digits:6'] : ['prohibited'],
        ];
    }

    public function newUser(): NewUser
    {
        return new NewUser($this->personName(), $this->email(), $this->string('role')->toString());
    }

    public function personName(): PersonName
    {
        return new PersonName($this->string('given_names')->toString(), $this->string('family_names')->toString());
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
