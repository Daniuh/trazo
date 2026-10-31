<?php

declare(strict_types=1);

// Catálogo de estados y sus transiciones permitidas.
// Documentado en docs/base-de-datos/diccionario/catalogos.md

namespace App\Modules\Incidents\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property int $sort_order
 * @property bool $is_initial
 * @property bool $is_pending
 * @property bool $is_final
 * @property bool $requires_note
 * @property bool $is_active
 */
class Status extends Model
{
    protected $fillable = ['code', 'name', 'sort_order', 'is_initial', 'is_pending', 'is_final', 'requires_note', 'is_active'];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_initial' => 'boolean',
            'is_pending' => 'boolean',
            'is_final' => 'boolean',
            'requires_note' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Estados a los que se puede pasar desde este.
     *
     * @return BelongsToMany<Status, $this>
     */
    public function nextStatuses(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'status_transitions', 'from_status_id', 'to_status_id');
    }
}
