<?php

declare(strict_types=1);

// Endpoints de sesión: iniciar, cerrar y consultar quién está conectado.
// Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Http;

use App\Modules\Auth\Application\LoginUser;
use App\Modules\Auth\Application\LogoutUser;
use App\Modules\Auth\Application\Ports\SessionUserReader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class AuthController
{
    use ResolvesSessionUser;

    public function __construct(private readonly SessionUserReader $users) {}

    public function login(LoginRequest $request, LoginUser $login, SessionPayload $payload): JsonResponse
    {
        $session = $login->handle($request->email(), $request->password(), (string) $request->ip());

        return new JsonResponse(['data' => $payload->from($session)]);
    }

    public function logout(Request $request, LogoutUser $logout): Response
    {
        $logout->handle($this->tokenId($request));

        return response()->noContent();
    }

    public function me(Request $request): JsonResponse
    {
        return new JsonResponse(['data' => SessionUserResource::from($this->users->find($this->user($request)->id))]);
    }
}
