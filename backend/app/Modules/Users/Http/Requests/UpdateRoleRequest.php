<?php

declare(strict_types=1);

// Validación para editar un rol: lo común (RoleRequest); el código no cambia y su propio nivel no cuenta como
// repetido. Documentado en docs/backend/users.md

namespace App\Modules\Users\Http\Requests;

use Illuminate\Validation\Rules\Unique;

final class UpdateRoleRequest extends RoleRequest
{
    protected function codeRules(): array
    {
        return [
            // No se envía: el código de un rol no cambia
            'code' => ['prohibited'],
        ];
    }

    protected function uniqueLevel(): Unique
    {
        $unique = parent::uniqueLevel();
        $current = $this->route('role');
        if (is_string($current)) {
            $unique->where(fn ($query) => $query->where('code', '!=', $current));
        }

        return $unique;
    }
}
