<?php

declare(strict_types=1);

// Registro del historial de un incidente. Es inmutable: el modelo impide editarlo o borrarlo.
// Documentado en docs/base-de-datos/diccionario/incident-activities.md

namespace App\Modules\Incidents\Infrastructure\Models;

use App\Modules\Users\Infrastructure\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * @property int $id
 * @property int $incident_id
 * @property int $user_id
 * @property int $activity_type_id
 * @property array<string, mixed>|null $changes
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property Carbon $created_at
 */
class IncidentActivity extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['incident_id', 'user_id', 'activity_type_id', 'changes', 'ip_address', 'user_agent'];

    protected function casts(): array
    {
        return ['changes' => 'array'];
    }

    protected static function booted(): void
    {
        $immutable = fn (): never => throw new LogicException('El historial de un incidente no se puede modificar ni borrar.');

        static::updating($immutable);
        static::deleting($immutable);
    }

    /**
     * @return BelongsTo<Incident, $this>
     */
    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<ActivityType, $this>
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(ActivityType::class, 'activity_type_id');
    }
}
