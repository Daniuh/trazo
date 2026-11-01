<?php

declare(strict_types=1);

// Validación para crear una persona: lo común (UserRequest), su rol y el código de quien administra si el rol es
// sensible. Documentado en docs/backend/users.md

namespace App\Modules\Users\Http\Requests;

use App\Modules\Users\Application\Data\NewUser;
use Illuminate\Validation\Rule;

final class CreateUserRequest extends UserRequest
{
    protected function requiredPermission(): string
    {
        return 'users.create';
    }

    protected function ownRules(): array
    {
        return [
            'role' => ['required', 'string', Rule::exists('roles', 'code')],
            'mfa_code' => ['nullable', 'string', 'digits:6'],
        ];
    }

    public function newUser(): NewUser
    {
        return new NewUser($this->personName(), $this->email(), $this->string('role')->toString());
    }

    public function mfaCode(): ?string
    {
        return $this->filled('mfa_code') ? $this->string('mfa_code')->toString() : null;
    }
}
