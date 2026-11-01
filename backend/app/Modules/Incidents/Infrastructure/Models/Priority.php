<?php

declare(strict_types=1);

// Catálogo de prioridades.
// Documentado en docs/base-de-datos/diccionario/catalogos.md

namespace App\Modules\Incidents\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property int $level
 * @property int $default_resolution_minutes
 * @property bool $is_active
 */
class Priority extends Model
{
    protected $fillable = ['code', 'name', 'level', 'default_resolution_minutes', 'is_active'];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'default_resolution_minutes' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
