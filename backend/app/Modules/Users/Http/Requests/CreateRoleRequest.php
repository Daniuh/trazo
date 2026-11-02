<?php

declare(strict_types=1);

// Validación para crear un rol: lo común (RoleRequest) y su código, único y en minúsculas.
// Documentado en docs/backend/users.md

namespace App\Modules\Users\Http\Requests;

use App\Modules\Users\Http\Rules\RoleCode;
use Illuminate\Validation\Rule;

final class CreateRoleRequest extends RoleRequest
{
    protected function codeRules(): array
    {
        return ['code' => ['required', 'string', 'min:3', 'max:40', new RoleCode, Rule::unique('roles', 'code')]];
    }

    public function code(): string
    {
        return $this->string('code')->toString();
    }
}
