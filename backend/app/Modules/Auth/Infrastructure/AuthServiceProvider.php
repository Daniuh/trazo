<?php

declare(strict_types=1);

// Conecta el módulo Auth con Laravel: implementaciones de los puertos, límites de intentos, inactividad de la sesión
// y el alcance del token de un ingreso a medias. Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Infrastructure;

use App\Modules\Auth\Application\Mfa\ConfirmMfaSetup;
use App\Modules\Auth\Application\Mfa\RegenerateRecoveryCodes;
use App\Modules\Auth\Application\Mfa\VerifyMfa;
use App\Modules\Auth\Application\Password\RequestPasswordReset;
use App\Modules\Auth\Application\Password\ResetPassword;
use App\Modules\Auth\Application\Password\VerifyResetCode;
use App\Modules\Auth\Application\Ports\BreachedPasswords;
use App\Modules\Auth\Application\Ports\CredentialVerifier;
use App\Modules\Auth\Application\Ports\MfaPolicies;
use App\Modules\Auth\Application\Ports\MfaStore;
use App\Modules\Auth\Application\Ports\OneTimePasswords;
use App\Modules\Auth\Application\Ports\PasswordResets;
use App\Modules\Auth\Application\Ports\ResetCodeSender;
use App\Modules\Auth\Application\Ports\SecurityNotifier;
use App\Modules\Auth\Application\Ports\SessionTokens;
use App\Modules\Auth\Application\Ports\SessionUserReader;
use App\Modules\Auth\Application\Ports\UserAccounts;
use App\Modules\Auth\Domain\LockoutPolicy;
use App\Modules\Auth\Domain\LoginAttemptStore;
use App\Modules\Shared\Domain\EmailAddress;
use App\Modules\Users\Infrastructure\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;
use PragmaRX\Google2FA\Google2FA;
use Psr\Log\LoggerInterface;

final class AuthServiceProvider extends ServiceProvider
{
    // Rutas que acepta el token de un ingreso a medias: el segundo paso y cerrar sesión
    private const CHALLENGE_ROUTES = ['auth.mfa.challenge.*', 'auth.logout'];

    public function register(): void
    {
        $this->app->bind(CredentialVerifier::class, EloquentCredentialVerifier::class);
        $this->app->bind(SessionUserReader::class, EloquentSessionUserReader::class);
        $this->app->bind(MfaStore::class, EloquentMfaStore::class);
        $this->app->bind(MfaPolicies::class, DatabaseMfaPolicies::class);
        $this->app->bind(UserAccounts::class, EloquentUserAccounts::class);
        $this->app->bind(ResetCodeSender::class, MailResetCodeSender::class);

        $this->app->bind(PasswordResets::class, fn (): PasswordResets => new DatabasePasswordResets(
            (string) config('app.key'),
        ));

        $this->app->bind(BreachedPasswords::class, fn ($app): BreachedPasswords => new HibpBreachedPasswords(
            $app->make(HttpClient::class),
            $app->make(LoggerInterface::class),
            (string) config('trazo.auth.password.common_list'),
            (int) config('trazo.auth.password.breach_check_timeout'),
        ));

        $this->app->bind(SessionTokens::class, fn (): SessionTokens => new SanctumSessionTokens(
            (int) config('sanctum.expiration'),
            (int) config('trazo.auth.mfa.challenge_minutes'),
        ));

        $this->app->bind(OneTimePasswords::class, fn (): OneTimePasswords => new Google2faOneTimePasswords(
            new Google2FA,
            (string) config('trazo.auth.mfa.issuer'),
        ));

        $this->app->bind(SecurityNotifier::class, fn (): SecurityNotifier => new MailSecurityNotifier(
            (string) config('trazo.dashboard.timezone'),
        ));

        $this->app->bind(LoginAttemptStore::class, fn ($app): LoginAttemptStore => new CacheLoginAttemptStore(
            $app->make(Repository::class),
            (int) config('trazo.auth.failures_ttl_minutes') * 60,
        ));

        $this->app->bind(LockoutPolicy::class, fn (): LockoutPolicy => new LockoutPolicy(
            array_map('intval', (array) config('trazo.auth.lockout')),
        ));

        $this->app->when(VerifyMfa::class)->needs('$maxAttempts')->give(fn (): int => (int) config('trazo.auth.mfa.max_attempts'));
        $this->app->when([ConfirmMfaSetup::class, RegenerateRecoveryCodes::class])
            ->needs('$recoveryCodes')
            ->give(fn (): int => (int) config('trazo.auth.mfa.recovery_codes'));

        $this->app->when(RequestPasswordReset::class)->needs('$codeMinutes')
            ->give(fn (): int => (int) config('trazo.auth.password.reset_code_minutes'));
        $this->app->when(VerifyResetCode::class)->needs('$ticketMinutes')
            ->give(fn (): int => (int) config('trazo.auth.password.reset_ticket_minutes'));
        $this->app->when([VerifyResetCode::class, ResetPassword::class])->needs('$maxAttempts')
            ->give(fn (): int => (int) config('trazo.auth.password.reset_max_attempts'));
    }

    public function boot(): void
    {
        // Los tokens solo pertenecen a usuarios; el tipo guardado es 'user' y la tabla lo exige con un CHECK
        Relation::enforceMorphMap(['user' => User::class]);

        RateLimiter::for('login', fn (Request $request): Limit => Limit::perMinute(5)->by(
            EmailAddress::normalize((string) $request->input('email')).'|'.$request->ip(),
        ));

        // Códigos del MFA: 5 por minuto por persona, igual que el login
        RateLimiter::for('mfa', fn (Request $request): Limit => Limit::perMinute(5)->by(
            'mfa|'.($request->user()?->getAuthIdentifier() ?? $request->ip()),
        ));

        // Recuperación de contraseña: 3 solicitudes cada 15 minutos y 5 intentos de código por minuto, por correo + IP
        RateLimiter::for('password-forgot', fn (Request $request): Limit => Limit::perMinutes(15, 3)->by(
            'forgot|'.EmailAddress::normalize((string) $request->input('email')).'|'.$request->ip(),
        ));
        RateLimiter::for('password-reset', fn (Request $request): Limit => Limit::perMinute(5)->by(
            'reset|'.EmailAddress::normalize((string) $request->input('email')).'|'.$request->ip(),
        ));
        // Cambiar la contraseña desde Mi cuenta: 5 por minuto por persona, porque pide la actual
        RateLimiter::for('password-change', fn (Request $request): Limit => Limit::perMinute(5)->by(
            'change|'.($request->user()?->getAuthIdentifier() ?? $request->ip()),
        ));

        // Además del vencimiento: una sesión sin uso se cierra sola, una cuenta desactivada pierde sus sesiones y el
        // token de un ingreso a medias solo abre el segundo paso
        Sanctum::authenticateAccessTokensUsing(function (PersonalAccessToken $token, bool $isValid): bool {
            $idleLimit = now()->subMinutes((int) config('trazo.auth.idle_minutes'));
            $recentlyUsed = ($token->last_used_at ?? $token->created_at)?->greaterThan($idleLimit) === true;
            $allowedHere = $token->can(SanctumSessionTokens::SESSION_ABILITY) || request()->routeIs(...self::CHALLENGE_ROUTES);

            return $isValid && $recentlyUsed && $allowedHere && $token->tokenable instanceof User && $token->tokenable->is_active;
        });
    }
}
