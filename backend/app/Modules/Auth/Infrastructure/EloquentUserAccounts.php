<?php

declare(strict_types=1);

// Cuentas para recuperar y cambiar la contraseña. El cast "hashed" del modelo la guarda con Argon2id.
// Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Infrastructure;

use App\Modules\Auth\Application\Password\Account;
use App\Modules\Auth\Application\Ports\UserAccounts;
use App\Modules\Shared\Domain\EmailAddress;
use App\Modules\Users\Infrastructure\Models\User;
use Illuminate\Contracts\Hashing\Hasher;

final readonly class EloquentUserAccounts implements UserAccounts
{
    public function __construct(private Hasher $hasher) {}

    public function findActiveByEmail(string $email): ?Account
    {
        $user = User::query()
            ->where('email', EmailAddress::normalize($email))
            ->where('is_active', true)
            ->first(['id', 'email', 'name']);

        return $user === null ? null : new Account($user->id, $user->email, $user->name);
    }

    public function find(int $userId): Account
    {
        $user = User::query()->findOrFail($userId, ['id', 'email', 'name']);

        return new Account($user->id, $user->email, $user->name);
    }

    public function passwordMatches(int $userId, string $password): bool
    {
        return $this->hasher->check($password, (string) User::query()->whereKey($userId)->value('password'));
    }

    public function updatePassword(int $userId, string $password): void
    {
        User::query()->findOrFail($userId)->forceFill(['password' => $password])->save();
    }
}
