<?php

declare(strict_types=1);

// Quién hace el pedido y con qué token. Las rutas que lo usan ya pasaron por auth:sanctum, así que siempre hay usuario.
// Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Http;

use App\Modules\Auth\Infrastructure\SanctumSessionTokens;
use App\Modules\Users\Infrastructure\Models\User;
use Illuminate\Http\Request;

trait ResolvesSessionUser
{
    private function user(Request $request): User
    {
        /** @var User */
        return $request->user();
    }

    private function tokenId(Request $request): int
    {
        return (int) $this->user($request)->currentAccessToken()->getKey();
    }

    // El token de un ingreso a medias solo tiene la habilidad del MFA
    private function isChallenge(Request $request): bool
    {
        return ! $this->user($request)->currentAccessToken()->can(SanctumSessionTokens::SESSION_ABILITY);
    }
}
