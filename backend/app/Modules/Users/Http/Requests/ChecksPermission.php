<?php

declare(strict_types=1);

// Revisa el permiso antes de validar: así quien no administra no puede averiguar, por los errores de validación, si
// un correo ya existe. Las reglas finas (jerarquía, quién a quién) siguen en los casos de uso.
// Documentado en docs/backend/users.md

namespace App\Modules\Users\Http\Requests;

use App\Modules\Users\Application\Ports\EffectivePermissions;

trait ChecksPermission
{
    public function authorize(EffectivePermissions $permissions): bool
    {
        $userId = (int) $this->user()?->getAuthIdentifier();

        return in_array($this->requiredPermission(), $permissions->of($userId), true);
    }

    abstract protected function requiredPermission(): string;
}
