<?php

declare(strict_types=1);

// Conecta el tiempo real con Laravel: quién puede escuchar cada canal (Channels::allows, con los permisos efectivos),
// el oyente de los cambios de incidentes y los avisos de sesión. Documentado en docs/backend/realtime.md

namespace App\Modules\Realtime\Infrastructure;

use App\Modules\Incidents\Application\Events\IncidentChanged;
use App\Modules\Realtime\Domain\Channels;
use App\Modules\Shared\Application\Ports\SessionSignals;
use App\Modules\Users\Application\Ports\EffectivePermissions;
use App\Modules\Users\Infrastructure\Models\User;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

final class RealtimeServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        SessionSignals::class => BroadcastSessionSignals::class,
    ];

    public function boot(): void
    {
        Event::listen(IncidentChanged::class, BroadcastIncidentChange::class);

        $allows = fn (string $channel) => fn (User $user): bool => Channels::allows(
            $channel,
            $user->ulid,
            $this->app->make(EffectivePermissions::class)->of($user->id),
        );

        Broadcast::channel(Channels::ALL_INCIDENTS, $allows(Channels::ALL_INCIDENTS));
        Broadcast::channel(Channels::UNASSIGNED_INCIDENTS, $allows(Channels::UNASSIGNED_INCIDENTS));
        Broadcast::channel(Channels::PERSONAL_PREFIX.'{ulid}', fn (User $user, string $ulid): bool => Channels::allows(
            Channels::personal($ulid),
            $user->ulid,
            [],
        ));
    }
}
