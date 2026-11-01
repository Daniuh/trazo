<?php

declare(strict_types=1);

// Firma de suscripciones: el id de la conexión (lo da Reverb, "123.456") y los canales privados a firmar
// ("private-…"), como máximo 10. Documentado en docs/backend/realtime.md

namespace App\Modules\Realtime\Http\Requests;

use App\Modules\Realtime\Http\Rules\SocketId;
use Illuminate\Foundation\Http\FormRequest;

final class ChannelAuthRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'socket_id' => ['required', 'string', new SocketId],
            'channels' => ['required', 'array', 'min:1', 'max:10'],
            'channels.*' => ['string', 'max:100'],
        ];
    }

    public function socketId(): string
    {
        return $this->string('socket_id')->toString();
    }

    /**
     * @return list<string>
     */
    public function channels(): array
    {
        return array_values(array_unique(array_map('strval', (array) $this->input('channels'))));
    }
}
