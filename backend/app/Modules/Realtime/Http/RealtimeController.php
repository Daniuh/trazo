<?php

declare(strict_types=1);

// Endpoints del tiempo real: cómo conectarse (la clave pública y la ruta del gateway, que cambian por entorno sin
// reconstruir el front) y la autorización de los canales privados. Las suscripciones de una pestaña se firman en una
// sola petición: cada petición a PHP cuesta, y al abrir la app se piden varias a la vez. Documentado en docs/backend/realtime.md

namespace App\Modules\Realtime\Http;

use App\Modules\Realtime\Http\Requests\ChannelAuthRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Broadcast;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final readonly class RealtimeController
{
    public const PATH = '/ws';

    public function config(): JsonResponse
    {
        return new JsonResponse(['data' => [
            'key' => (string) config('broadcasting.connections.reverb.key'),
            'path' => self::PATH,
        ]]);
    }

    // Una firma por canal permitido; null en los que no (RealtimeServiceProvider decide)
    public function authorize(ChannelAuthRequest $request): JsonResponse
    {
        $signatures = [];
        foreach ($request->channels() as $channel) {
            // Broadcast::auth firma un canal por petición: se le pasa una copia con ese canal (y la misma persona)
            $single = $request->duplicate();
            $single->setJson(new InputBag(['socket_id' => $request->socketId(), 'channel_name' => $channel]));
            try {
                $signatures[$channel] = Broadcast::auth($single);
            } catch (AccessDeniedHttpException) {
                $signatures[$channel] = null;
            }
        }

        return new JsonResponse(['data' => $signatures]);
    }
}
