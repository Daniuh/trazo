<?php

declare(strict_types=1);

// Modelo Eloquent de incidentes: borrado lógico, ULID público y relaciones con catálogos y usuarios.
// Documentado en docs/base-de-datos/diccionario/incidents.md

namespace App\Modules\Incidents\Infrastructure\Models;

use App\Modules\Users\Infrastructure\Models\User;
use Database\Factories\IncidentFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $ulid
 * @property string $code
 * @property string $title
 * @property string $description
 * @property int $priority_id
 * @property int $status_id
 * @property int $created_by
 * @property int|null $assigned_to
 * @property Carbon $due_date
 * @property Carbon|null $resolved_at
 * @property int $lock_version
 */
#[UseFactory(IncidentFactory::class)]
class Incident extends Model
{
    /** @use HasFactory<IncidentFactory> */
    use HasFactory;

    use HasUlids;
    use SoftDeletes;

    protected $fillable = [
        'code', 'title', 'description', 'priority_id', 'status_id',
        'created_by', 'assigned_to', 'due_date', 'resolved_at', 'lock_version',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'datetime',
            'resolved_at' => 'datetime',
            'lock_version' => 'integer',
        ];
    }

    /**
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    /**
     * @return BelongsTo<Priority, $this>
     */
    public function priority(): BelongsTo
    {
        return $this->belongsTo(Priority::class);
    }

    /**
     * @return BelongsTo<Status, $this>
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * @return HasMany<IncidentActivity, $this>
     */
    public function activities(): HasMany
    {
        return $this->hasMany(IncidentActivity::class);
    }

    /**
     * La solicitud de reapertura en curso, si hay una (nunca más de una).
     *
     * @return HasOne<IncidentReopenRequest, $this>
     */
    public function pendingReopenRequest(): HasOne
    {
        return $this->hasOne(IncidentReopenRequest::class)->where('status', IncidentReopenRequest::PENDING);
    }
}
