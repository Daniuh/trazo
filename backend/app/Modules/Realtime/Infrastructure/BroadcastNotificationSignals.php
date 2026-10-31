<?php

declare(strict_types=1);

// Implementa NotificationSignals: avisa por el canal personal de cada persona que sus notificaciones cambiaron.
// La pestaña desde la que se hizo el cambio no recibe el aviso (toOthers). Documentado en docs/backend/realtime.md

namespace App\Modules\Realtime\Infrastructure;

use App\Modules\Notifications\Application\Ports\NotificationSignals;
use App\Modules\Realtime\Domain\Channels;
use App\Modules\Realtime\Infrastructure\Events\NotificationsSignal;
use Illuminate\Support\Facades\DB;

final class BroadcastNotificationSignals implements NotificationSignals
{
    public function changed(array $users): void
    {
        if ($users === []) {
            return;
        }

        $channels = array_values(DB::table('users')->whereIn('id', $users)->pluck('ulid')
            ->map(fn (mixed $ulid): string => Channels::personal((string) $ulid))->all());
        if ($channels !== []) {
            broadcast(new NotificationsSignal($channels))->toOthers();
        }
    }
}
