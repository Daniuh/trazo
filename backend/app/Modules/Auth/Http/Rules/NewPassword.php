<?php

declare(strict_types=1);

// Reglas de toda contraseña nueva (recuperar o cambiar): largo de la política, confirmación, que no sea fácil de adivinar
// y que no esté filtrada. "bail" corta en la primera falla: no se consulta Have I Been Pwned por una contraseña que ya
// es muy corta o repetitiva. Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Http\Rules;

use App\Modules\Auth\Domain\Password\PasswordPolicy;

final class NewPassword
{
    /**
     * @param  list<string>  $personal  datos de la persona (correo, nombre) que no deben aparecer en la contraseña
     * @return list<mixed>
     */
    public static function rules(array $personal = []): array
    {
        return [
            'bail',
            'required',
            'string',
            'min:'.PasswordPolicy::MIN_LENGTH,
            'max:'.PasswordPolicy::MAX_LENGTH,
            'confirmed',
            new NotWeakPassword($personal),
            app(NotBreachedPassword::class),
        ];
    }
}
