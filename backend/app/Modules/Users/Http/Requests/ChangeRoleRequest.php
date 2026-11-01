<?php

declare(strict_types=1);

// Validación para cambiar el rol de una persona: el rol nuevo (que exista) y el código de quien administra.
// Documentado en docs/backend/users.md

namespace App\Modules\Users\Http\Requests;

use Illuminate\Validation\Rule;

final class ChangeRoleRequest extends UserAccessRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [...parent::rules(), 'role' => ['required', 'string', Rule::exists('roles', 'code')]];
    }

    public function role(): string
    {
        return $this->string('role')->toString();
    }
}
