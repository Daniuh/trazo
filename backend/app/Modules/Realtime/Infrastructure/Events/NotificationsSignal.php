<?php

declare(strict_types=1);

// "Tus notificaciones cambiaron": llegó una nueva o se marcaron como leídas en otra pestaña. Va sin datos: la campana
// vuelve a pedir las sin leer a la API. Documentado en docs/backend/realtime.md

namespace App\Modules\Realtime\Infrastructure\Events;

final class NotificationsSignal extends Signal
{
    public function broadcastAs(): string
    {
        return 'notifications.changed';
    }

    public function broadcastWith(): array
    {
        return [];
    }
}
