<?php

declare(strict_types=1);

// "Este incidente cambió": su ULID y qué le pasó (created, updated, deleted o restored).
// Documentado en docs/backend/realtime.md

namespace App\Modules\Realtime\Infrastructure\Events;

use App\Modules\Incidents\Application\Events\IncidentChange;

final class IncidentSignal extends Signal
{
    /**
     * @param  list<string>  $channels
     */
    public function __construct(array $channels, public readonly string $incident, public readonly IncidentChange $change)
    {
        parent::__construct($channels);
    }

    public function broadcastAs(): string
    {
        return 'incident.changed';
    }

    public function broadcastWith(): array
    {
        return ['id' => $this->incident, 'change' => $this->change->value];
    }
}
