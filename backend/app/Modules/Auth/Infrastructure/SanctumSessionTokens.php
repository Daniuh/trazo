<?php

declare(strict_types=1);

// Tokens de sesión con Sanctum: se guardan con hash y vencen a las 8 horas como máximo.
// Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Infrastructure;

use App\Modules\Auth\Application\IssuedSession;
use App\Modules\Auth\Application\Ports\SessionTokens;
use App\Modules\Users\Infrastructure\Models\User;
use DateTimeImmutable;
use Laravel\Sanctum\PersonalAccessToken;

final readonly class SanctumSessionTokens implements SessionTokens
{
    private const TOKEN_NAME = 'session';

    public function __construct(private int $maxMinutes) {}

    public function issue(int $userId): IssuedSession
    {
        $expiresAt = new DateTimeImmutable("+{$this->maxMinutes} minutes");
        $token = User::query()->findOrFail($userId)->createToken(self::TOKEN_NAME, ['*'], $expiresAt);

        return new IssuedSession($userId, $token->plainTextToken, $expiresAt);
    }

    public function revoke(int $tokenId): void
    {
        PersonalAccessToken::query()->whereKey($tokenId)->delete();
    }
}
