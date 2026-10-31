<?php

declare(strict_types=1);

// Guarda el MFA de cada persona en user_mfa_factors y user_recovery_codes. Las operaciones que no deben repetirse
// (usar un código, canjear uno de recuperación) se resuelven con un UPDATE condicional, seguro con pedidos a la vez.
// Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Infrastructure;

use App\Modules\Auth\Application\Mfa\MfaFactor;
use App\Modules\Auth\Application\Ports\MfaStore;
use App\Modules\Auth\Domain\Mfa\RecoveryCode;
use App\Modules\Auth\Infrastructure\Models\UserMfaFactor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class EloquentMfaStore implements MfaStore
{
    private const METHOD = 'totp';

    public function find(int $userId): ?MfaFactor
    {
        $factor = $this->query($userId)->first();

        return $factor === null ? null : new MfaFactor($factor->secret, $factor->confirmed_at !== null, $factor->last_used_step);
    }

    public function startSetup(int $userId, string $secret): void
    {
        UserMfaFactor::query()->updateOrCreate(
            ['user_id' => $userId, 'method' => self::METHOD],
            ['secret' => $secret, 'confirmed_at' => null, 'last_used_step' => null],
        );
    }

    public function activate(int $userId, int $step, array $recoveryCodes): void
    {
        DB::transaction(function () use ($userId, $step, $recoveryCodes): void {
            $this->query($userId)->update(['confirmed_at' => now(), 'last_used_step' => $step]);
            $this->replaceRecoveryCodes($userId, $recoveryCodes);
        });
    }

    public function recordUse(int $userId, int $step): bool
    {
        return $this->query($userId)
            ->where(fn ($query) => $query->whereNull('last_used_step')->orWhere('last_used_step', '<', $step))
            ->update(['last_used_step' => $step]) === 1;
    }

    public function consumeRecoveryCode(int $userId, string $code): bool
    {
        return DB::table('user_recovery_codes')
            ->where('user_id', $userId)
            ->where('code_hash', RecoveryCode::hash($code))
            ->whereNull('used_at')
            ->update(['used_at' => now()]) === 1;
    }

    public function replaceRecoveryCodes(int $userId, array $recoveryCodes): void
    {
        DB::transaction(function () use ($userId, $recoveryCodes): void {
            DB::table('user_recovery_codes')->where('user_id', $userId)->delete();
            DB::table('user_recovery_codes')->insert(array_map(fn (string $code): array => [
                'user_id' => $userId,
                'code_hash' => RecoveryCode::hash($code),
            ], $recoveryCodes));
        });
    }

    public function remainingRecoveryCodes(int $userId): int
    {
        return DB::table('user_recovery_codes')->where('user_id', $userId)->whereNull('used_at')->count();
    }

    public function remove(int $userId): void
    {
        DB::transaction(function () use ($userId): void {
            DB::table('user_recovery_codes')->where('user_id', $userId)->delete();
            $this->query($userId)->delete();
        });
    }

    /** @return Builder<UserMfaFactor> */
    private function query(int $userId): Builder
    {
        return UserMfaFactor::query()->where('user_id', $userId)->where('method', self::METHOD);
    }
}
