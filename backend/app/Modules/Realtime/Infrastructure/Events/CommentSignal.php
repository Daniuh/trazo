<?php

declare(strict_types=1);

// "La conversación de este incidente cambió": su ULID y si llegó un mensaje (added) o se borró uno (removed).
// Sin el texto: quien lo recibe vuelve a pedir la conversación a la API. Documentado en docs/backend/realtime.md

namespace App\Modules\Realtime\Infrastructure\Events;

final class CommentSignal extends Signal
{
    /**
     * @param  list<string>  $channels
     */
    public function __construct(array $channels, public readonly string $incident, public readonly bool $removed)
    {
        parent::__construct($channels);
    }

    public function broadcastAs(): string
    {
        return 'incident.commented';
    }

    public function broadcastWith(): array
    {
        return ['id' => $this->incident, 'change' => $this->removed ? 'removed' : 'added'];
    }
}
