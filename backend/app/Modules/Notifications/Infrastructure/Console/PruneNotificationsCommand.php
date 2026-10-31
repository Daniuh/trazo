<?php

declare(strict_types=1);

// Limpieza diaria: borra las notificaciones creadas hace más de trazo.notifications.retention_days.
// Documentado en docs/backend/notifications.md

namespace App\Modules\Notifications\Infrastructure\Console;

use App\Modules\Notifications\Application\PersonalNotifications;
use Illuminate\Console\Command;

final class PruneNotificationsCommand extends Command
{
    protected $signature = 'notifications:prune';

    protected $description = 'Borra las notificaciones viejas';

    public function handle(PersonalNotifications $notifications): int
    {
        $this->info('Notificaciones borradas: '.$notifications->prune());

        return self::SUCCESS;
    }
}
