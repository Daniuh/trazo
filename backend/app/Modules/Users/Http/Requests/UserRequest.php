<?php

declare(strict_types=1);

// Base de las validaciones para crear y editar una persona: nombres y apellidos, y correo normalizado y único. Lo propio
// de cada una (rol y código de quien administra al crear) está en CreateUserRequest y UpdateUserRequest.
// Documentado en docs/backend/users.md

namespace App\Modules\Users\Http\Requests;

use App\Modules\Shared\Domain\EmailAddress;
use App\Modules\Users\Domain\PersonName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

abstract class UserRequest extends FormRequest
{
    use ChecksPermission;

    /**
     * Reglas propias de crear o de editar.
     *
     * @return array<string, list<mixed>>
     */
    abstract protected function ownRules(): array;

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
        return [
            'given_names' => ['required', 'string', 'max:'.PersonName::MAX_PART],
            'family_names' => ['required', 'string', 'max:'.PersonName::MAX_PART],
            // No se envía: el nombre completo lo arma la base
            'name' => ['prohibited'],
            'email' => ['required', 'string', 'email:rfc', 'max:191', $this->uniqueEmail()],
            ...$this->ownRules(),
        ];
    }

    // Al editar, la propia persona no cuenta como repetida
    protected function uniqueEmail(): Unique
    {
        return Rule::unique('users', 'email');
    }

    public function personName(): PersonName
    {
        return new PersonName($this->string('given_names')->toString(), $this->string('family_names')->toString());
    }

    public function email(): string
    {
        return $this->string('email')->toString();
    }
}
