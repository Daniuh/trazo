<?php

declare(strict_types=1);

// Base de los avisos de tiempo real: van por la cola (no frenan la respuesta), salen solo si la transacción se
// confirma y nunca llevan datos del negocio, solo identificadores. Quien recibe vuelve a consultar la API, que aplica
// sus permisos. Documentado en docs/backend/realtime.md

namespace App\Modules\Realtime\Infrastructure\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

abstract class Signal implements ShouldBroadcast
{
    use InteractsWithSockets;

    public bool $afterCommit = true;

    /**
     * @param  list<string>  $channels
     */
    public function __construct(private readonly array $channels) {}

    /**
     * @return list<PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return array_map(fn (string $channel): PrivateChannel => new PrivateChannel($channel), $this->channels);
    }

    abstract public function broadcastAs(): string;

    /**
     * @return array<string, string>
     */
    abstract public function broadcastWith(): array;
}
