<?php

declare(strict_types=1);

// "Tu sesión cambió": rol, permisos, estado o sesiones cerradas. Va sin datos: cada pantalla abierta vuelve a pedir
// /auth/me, que dice si la sesión sigue y con qué permisos. Documentado en docs/backend/realtime.md

namespace App\Modules\Realtime\Infrastructure\Events;

final class SessionSignal extends Signal
{
    public function broadcastAs(): string
    {
        return 'session.changed';
    }

    public function broadcastWith(): array
    {
        return [];
    }
}
