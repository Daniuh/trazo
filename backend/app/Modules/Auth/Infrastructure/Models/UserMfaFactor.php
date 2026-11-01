<?php

declare(strict_types=1);

// Modelo de user_mfa_factors. El secreto se cifra y descifra solo con la clave de la aplicación (cast encrypted):
// en la base, en un respaldo o en un log nunca aparece en claro. Documentado en docs/base-de-datos/diccionario/mfa.md

namespace App\Modules\Auth\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $method
 * @property string $secret
 * @property Carbon|null $confirmed_at
 * @property int|null $last_used_step
 */
final class UserMfaFactor extends Model
{
    protected $table = 'user_mfa_factors';

    protected $fillable = ['user_id', 'method', 'secret', 'confirmed_at', 'last_used_step'];

    protected $hidden = ['secret'];

    protected function casts(): array
    {
        return [
            'secret' => 'encrypted',
            'confirmed_at' => 'datetime',
            'last_used_step' => 'integer',
        ];
    }
}
