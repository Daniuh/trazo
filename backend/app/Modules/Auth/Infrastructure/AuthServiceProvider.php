<?php

declare(strict_types=1);

// Conecta el módulo Auth con Laravel: implementaciones de los puertos, límite de intentos e inactividad de la sesión.
// Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Infrastructure;

use App\Modules\Auth\Application\Ports\CredentialVerifier;
use App\Modules\Auth\Application\Ports\SessionTokens;
use App\Modules\Auth\Application\Ports\SessionUserReader;
use App\Modules\Auth\Domain\LockoutPolicy;
use App\Modules\Auth\Domain\LoginAttemptStore;
use App\Modules\Shared\Domain\EmailAddress;
use App\Modules\Users\Infrastructure\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;

final class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CredentialVerifier::class, EloquentCredentialVerifier::class);
        $this->app->bind(SessionUserReader::class, EloquentSessionUserReader::class);

        $this->app->bind(SessionTokens::class, fn (): SessionTokens => new SanctumSessionTokens(
            (int) config('sanctum.expiration'),
        ));

        $this->app->bind(LoginAttemptStore::class, fn ($app): LoginAttemptStore => new CacheLoginAttemptStore(
            $app->make(Repository::class),
            (int) config('trazo.auth.failures_ttl_minutes') * 60,
        ));

        $this->app->bind(LockoutPolicy::class, fn (): LockoutPolicy => new LockoutPolicy(
            array_map('intval', (array) config('trazo.auth.lockout')),
        ));
    }

    public function boot(): void
    {
        // Los tokens solo pertenecen a usuarios; el tipo guardado es 'user' y la tabla lo exige con un CHECK
        Relation::enforceMorphMap(['user' => User::class]);

        RateLimiter::for('login', fn (Request $request): Limit => Limit::perMinute(5)->by(
            EmailAddress::normalize((string) $request->input('email')).'|'.$request->ip(),
        ));

        // Además del vencimiento a las 8 horas: una sesión sin uso se cierra sola, y una cuenta desactivada pierde sus sesiones
        Sanctum::authenticateAccessTokensUsing(function (PersonalAccessToken $token, bool $isValid): bool {
            $idleLimit = now()->subMinutes((int) config('trazo.auth.idle_minutes'));
            $recentlyUsed = ($token->last_used_at ?? $token->created_at)?->greaterThan($idleLimit) === true;

            return $isValid && $recentlyUsed && $token->tokenable instanceof User && $token->tokenable->is_active;
        });
    }
}
