<?php

declare(strict_types=1);

// Solicitud de reapertura de un incidente cerrado o cancelado, con su decisión.
// Documentado en docs/base-de-datos/diccionario/incident-reopen-requests.md

namespace App\Modules\Incidents\Infrastructure\Models;

use App\Modules\Users\Infrastructure\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $incident_id
 * @property int $requested_by
 * @property string $reason
 * @property string $status
 * @property int|null $decided_by
 * @property string|null $decision_note
 * @property Carbon|null $decided_at
 * @property Carbon $created_at
 */
class IncidentReopenRequest extends Model
{
    public const PENDING = 'pending';

    public const APPROVED = 'approved';

    public const REJECTED = 'rejected';

    protected $fillable = ['incident_id', 'requested_by', 'reason', 'status', 'decided_by', 'decision_note', 'decided_at'];

    protected function casts(): array
    {
        return ['decided_at' => 'datetime'];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
