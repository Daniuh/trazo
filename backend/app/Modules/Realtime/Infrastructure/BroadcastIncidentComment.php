<?php

declare(strict_types=1);

// Escucha IncidentCommented y avisa a los mismos canales que un cambio del incidente (Channels::forIncident): quien
// puede verlo se entera de que su conversación cambió. Quien escribió no recibe el aviso en la pestaña desde la que
// lo hizo (toOthers). Documentado en docs/backend/realtime.md

namespace App\Modules\Realtime\Infrastructure;

use App\Modules\Incidents\Application\Events\IncidentCommented;
use App\Modules\Realtime\Domain\Channels;
use App\Modules\Realtime\Infrastructure\Events\CommentSignal;
use Illuminate\Support\Facades\DB;

final readonly class BroadcastIncidentComment
{
    public function handle(IncidentCommented $event): void
    {
        $ulids = DB::table('users')->whereIn('id', array_filter([$event->createdBy, $event->assignedTo]))->pluck('ulid', 'id');
        $assignee = $event->assignedTo === null ? null : (string) $ulids[$event->assignedTo];
        $unassigned = $event->assignedTo === null;

        $channels = Channels::forIncident((string) $ulids[$event->createdBy], $assignee, $assignee, wasUnassigned: $unassigned, isUnassigned: $unassigned);
        broadcast(new CommentSignal($channels, $event->incidentUlid, $event->removed))->toOthers();
    }
}
