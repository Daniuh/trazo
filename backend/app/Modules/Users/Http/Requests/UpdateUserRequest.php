<?php

declare(strict_types=1);

// Validación para editar los datos de una persona: lo común (UserRequest). El rol y los permisos tienen sus propios
// endpoints, así que aquí no se aceptan. Documentado en docs/backend/users.md

namespace App\Modules\Users\Http\Requests;

use Illuminate\Validation\Rules\Unique;

final class UpdateUserRequest extends UserRequest
{
    protected function requiredPermission(): string
    {
        return 'users.update';
    }

    protected function ownRules(): array
    {
        return [
            // No se envía: el rol tiene su propio endpoint
            'role' => ['prohibited'],
            // No se envía: editar los datos no pide el código de quien administra
            'mfa_code' => ['prohibited'],
        ];
    }

    protected function uniqueEmail(): Unique
    {
        $unique = parent::uniqueEmail();
        $current = $this->route('user');
        if (is_string($current)) {
            $unique->where(fn ($query) => $query->where('ulid', '!=', strtolower($current)));
        }

        return $unique;
    }
}
