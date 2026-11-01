<?php

declare(strict_types=1);

// Rol: agrupa permisos granulares. El código consulta permisos, nunca nombres de rol.
// Documentado en docs/base-de-datos/diccionario/roles-y-permisos.md

namespace App\Modules\Users\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property int $level
 */
class Role extends Model
{
    protected $fillable = ['code', 'name', 'level'];

    /**
     * @return BelongsToMany<Permission, $this>
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permission');
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_role');
    }
}
