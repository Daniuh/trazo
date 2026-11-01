<?php

declare(strict_types=1);

// Catálogo de tipos de actividad del historial.
// Documentado en docs/base-de-datos/diccionario/catalogos.md

namespace App\Modules\Incidents\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 */
class ActivityType extends Model
{
    protected $fillable = ['code', 'name'];
}
