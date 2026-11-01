<?php

declare(strict_types=1);

// Tokens de sesión con Sanctum: se guardan con hash. El de sesión completa vence a las 8 horas como máximo; el de un
// ingreso a medias (MFA pendiente) solo tiene la habilidad "mfa" y vence en pocos minutos.
// Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Infrastructure;

use App\Modules\Auth\Application\IssuedSession;
use App\Modules\Auth\Application\Ports\SessionTokens;
use App\Modules\Auth\Domain\Mfa\LoginStep;
use App\Modules\Users\Infrastructure\Models\User;
use DateTimeImmutable;
use Laravel\Sanctum\PersonalAccessToken;

final readonly class SanctumSessionTokens implements SessionTokens
{
    private const TOKEN_NAME = 'session';

    private const CHALLENGE_NAME = 'mfa';

    // Habilidad de los tokens de sesión completa; la revisa el autenticador en AuthServiceProvider
    public const SESSION_ABILITY = 'session';

    public const CHALLENGE_ABILITY = 'mfa';

    public function __construct(
        private int $maxMinutes,
        private int $challengeMinutes,
    ) {}

    public function issue(int $userId, LoginStep $step = LoginStep::Complete): IssuedSession
    {
        $session = $this->create($userId, self::TOKEN_NAME, [self::SESSION_ABILITY], $this->maxMinutes, $step);

        // Toda sesión completa nace aquí, así que es el único lugar que anota el último ingreso
        User::query()->whereKey($userId)->update(['last_login_at' => now()]);

        return $session;
    }

    public function issueChallenge(int $userId, LoginStep $step): IssuedSession
    {
        return $this->create($userId, self::CHALLENGE_NAME, [self::CHALLENGE_ABILITY], $this->challengeMinutes, $step);
    }

    public function revoke(int $tokenId): void
    {
        PersonalAccessToken::query()->whereKey($tokenId)->delete();
    }

    public function revokeAll(int $userId, ?int $exceptTokenId = null): void
    {
        PersonalAccessToken::query()
            ->where('tokenable_type', 'user')
            ->where('tokenable_id', $userId)
            ->when($exceptTokenId !== null, fn ($query) => $query->whereKeyNot($exceptTokenId))
            ->delete();
    }

    /**
     * @param  list<string>  $abilities
     */
    private function create(int $userId, string $name, array $abilities, int $minutes, LoginStep $step): IssuedSession
    {
        $expiresAt = new DateTimeImmutable("+{$minutes} minutes");
        $token = User::query()->findOrFail($userId)->createToken($name, $abilities, $expiresAt);

        return new IssuedSession($userId, $token->plainTextToken, $expiresAt, $step);
    }
}
