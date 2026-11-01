<?php

declare(strict_types=1);

// Validación para fijar los permisos de una persona: la lista completa que debe tener (existentes y sin repetir) y el
// código de quien administra. Documentado en docs/backend/users.md

namespace App\Modules\Users\Http\Requests;

use Illuminate\Validation\Rule;

final class ChangePermissionsRequest extends UserAccessRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'permissions' => ['present', 'array', 'max:200'],
            'permissions.*' => ['string', 'distinct', Rule::exists('permissions', 'code')],
        ];
    }

    /**
     * @return list<string>
     */
    public function permissions(): array
    {
        return array_values(array_map('strval', (array) $this->input('permissions', [])));
    }
}
