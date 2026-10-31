<?php

declare(strict_types=1);

// Reglas de toda contraseña nueva (recuperar o cambiar): largo de la política, confirmación y que no esté filtrada.
// "bail" corta en la primera falla: no se consulta Have I Been Pwned por una contraseña que ya es muy corta.
// Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Http\Rules;

use App\Modules\Auth\Domain\Password\PasswordPolicy;

final class NewPassword
{
    /**
     * @return list<mixed>
     */
    public static function rules(): array
    {
        return [
            'bail',
            'required',
            'string',
            'min:'.PasswordPolicy::MIN_LENGTH,
            'max:'.PasswordPolicy::MAX_LENGTH,
            'confirmed',
            app(NotBreachedPassword::class),
        ];
    }
}
