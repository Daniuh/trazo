<?php

declare(strict_types=1);

// Permiso granular: módulo.acción[.alcance].
// Documentado en docs/base-de-datos/diccionario/roles-y-permisos.md

namespace App\Modules\Users\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 */
class Permission extends Model
{
    protected $fillable = ['code', 'name'];

    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permission');
    }
}
