<?php

declare(strict_types=1);

// Implementa SessionSignals: avisa por el canal personal de cada persona que su sesión cambió. El aviso sale cuando
// la transacción se confirma. Documentado en docs/backend/realtime.md

namespace App\Modules\Realtime\Infrastructure;

use App\Modules\Realtime\Domain\Channels;
use App\Modules\Realtime\Infrastructure\Events\SessionSignal;
use App\Modules\Shared\Application\Ports\SessionSignals;
use Illuminate\Support\Facades\DB;

final class BroadcastSessionSignals implements SessionSignals
{
    public function changed(int ...$userIds): void
    {
        if ($userIds === []) {
            return;
        }

        $channels = array_values(DB::table('users')->whereIn('id', $userIds)->pluck('ulid')
            ->map(fn (mixed $ulid): string => Channels::personal((string) $ulid))->all());
        if ($channels !== []) {
            broadcast(new SessionSignal($channels));
        }
    }
}
