<?php

declare(strict_types=1);

// Endpoints de sesión: iniciar, cerrar y consultar quién está conectado.
// Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Http;

use App\Modules\Auth\Application\LoginUser;
use App\Modules\Auth\Application\LogoutUser;
use App\Modules\Auth\Application\Ports\SessionUserReader;
use App\Modules\Users\Infrastructure\Models\User;
use DateTimeInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class AuthController
{
    public function __construct(private readonly SessionUserReader $users) {}

    public function login(LoginRequest $request, LoginUser $login): JsonResponse
    {
        $session = $login->handle($request->email(), $request->password(), (string) $request->ip());

        return new JsonResponse(['data' => [
            'token' => $session->token,
            'token_type' => 'Bearer',
            'expires_at' => $session->expiresAt->format(DateTimeInterface::ATOM),
            'user' => SessionUserResource::from($this->users->find($session->userId)),
        ]]);
    }

    public function logout(Request $request, LogoutUser $logout): Response
    {
        $logout->handle((int) $this->user($request)->currentAccessToken()->getKey());

        return response()->noContent();
    }

    public function me(Request $request): JsonResponse
    {
        return new JsonResponse(['data' => SessionUserResource::from($this->users->find($this->user($request)->id))]);
    }

    // Las rutas que llegan aquí ya pasaron por auth:sanctum, así que siempre hay usuario
    private function user(Request $request): User
    {
        /** @var User */
        return $request->user();
    }
}
