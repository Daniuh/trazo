<?php

declare(strict_types=1);

// Verifica credenciales contra la tabla users con Argon2id, sin dejar pistas por el tiempo de respuesta.
// Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Infrastructure;

use App\Modules\Auth\Application\Ports\CredentialVerifier;
use App\Modules\Shared\Domain\EmailAddress;
use App\Modules\Users\Infrastructure\Models\User;
use Illuminate\Contracts\Hashing\Hasher;

final readonly class EloquentCredentialVerifier implements CredentialVerifier
{
    // Hash de un valor aleatorio con los mismos parámetros de Argon2id. Si el correo no existe,
    // se verifica contra él para que la respuesta tarde lo mismo que con un correo real.
    private const DUMMY_HASH = '$argon2id$v=19$m=65536,t=4,p=1$czdwN1ptQ3pFd29KOGdqUw$l1riAPaI9x2pvh5BXZpjcL+7CY2VakvBriL4hVUYl3s';

    public function __construct(private Hasher $hasher) {}

    public function verify(string $email, string $password): ?int
    {
        $user = User::query()->where('email', EmailAddress::normalize($email))->first();

        if ($user === null) {
            $this->hasher->check($password, self::DUMMY_HASH);

            return null;
        }

        $hash = (string) $user->getAuthPassword();
        if (! $this->hasher->check($password, $hash) || ! $user->is_active) {
            return null;
        }

        // Si cambian los parámetros de Argon2id, la contraseña se actualiza en el siguiente ingreso
        if ($this->hasher->needsRehash($hash)) {
            $user->forceFill(['password' => $password])->save();
        }

        return $user->id;
    }
}
