<?php

declare(strict_types=1);

// Escucha IncidentChanged y avisa a los canales que corresponden (Channels::forIncident). Si el incidente quedó
// asignado a otra persona, le avisa además por su canal personal. Quien hizo el cambio no recibe el aviso en la
// pestaña desde la que lo hizo (toOthers, con la cabecera X-Socket-ID). Documentado en docs/backend/realtime.md

namespace App\Modules\Realtime\Infrastructure;

use App\Modules\Incidents\Application\Events\IncidentChange;
use App\Modules\Incidents\Application\Events\IncidentChanged;
use App\Modules\Realtime\Domain\Channels;
use App\Modules\Realtime\Infrastructure\Events\AssignedSignal;
use App\Modules\Realtime\Infrastructure\Events\IncidentSignal;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Support\Facades\DB;

final readonly class BroadcastIncidentChange
{
    public function __construct(private Auth $auth) {}

    public function handle(IncidentChanged $event): void
    {
        $ulids = $this->ulids([$event->createdBy, $event->assigneeBefore, $event->assigneeAfter]);
        $before = $event->assigneeBefore === null ? null : ($ulids[$event->assigneeBefore] ?? null);
        $after = $event->assigneeAfter === null ? null : ($ulids[$event->assigneeAfter] ?? null);

        $channels = Channels::forIncident(
            $ulids[$event->createdBy] ?? null,
            $before,
            $after,
            // Un incidente recién creado no estaba en ningún lado: solo cuenta cómo quedó
            wasUnassigned: $event->assigneeBefore === null && $event->change !== IncidentChange::Created,
            isUnassigned: $event->assigneeAfter === null,
        );
        broadcast(new IncidentSignal($channels, $event->ulid, $event->change))->toOthers();

        // Tomar un incidente uno mismo no es una noticia para uno mismo
        $actor = $this->auth->guard()->id();
        if ($after !== null && $event->assigneeChanged() && $event->assigneeAfter !== $actor) {
            broadcast(new AssignedSignal($after, $event->ulid));
        }
    }

    /**
     * @param  list<int|null>  $ids
     * @return array<int, string>
     */
    private function ulids(array $ids): array
    {
        return DB::table('users')->whereIn('id', array_filter($ids))->pluck('ulid', 'id')
            ->map(fn (mixed $ulid): string => (string) $ulid)->all();
    }
}
