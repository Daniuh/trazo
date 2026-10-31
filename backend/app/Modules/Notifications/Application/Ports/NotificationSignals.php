<?php

declare(strict_types=1);

// Avisa en tiempo real a cada persona que sus notificaciones cambiaron, para que su campana se actualice.
// Documentado en docs/backend/notifications.md

namespace App\Modules\Notifications\Application\Ports;

interface NotificationSignals
{
    /**
     * @param  list<int>  $users
     */
    public function changed(array $users): void;
}
