<?php

declare(strict_types=1);

// "Te asignaron este incidente", por el canal personal de quien lo recibe. Solo el ULID: el front pide el código y el
// título a la API para mostrar el aviso. Documentado en docs/backend/realtime.md

namespace App\Modules\Realtime\Infrastructure\Events;

use App\Modules\Realtime\Domain\Channels;

final class AssignedSignal extends Signal
{
    public function __construct(string $assignee, public readonly string $incident)
    {
        parent::__construct([Channels::personal($assignee)]);
    }

    public function broadcastAs(): string
    {
        return 'incident.assigned';
    }

    public function broadcastWith(): array
    {
        return ['id' => $this->incident];
    }
}
