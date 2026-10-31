<?php

declare(strict_types=1);

// Solicitudes de recuperación en password_reset_codes. Código y pase se guardan con HMAC-SHA256 usando la clave de la
// aplicación: con solo 6 dígitos, un hash simple se descifraría en segundos con una copia de la base.
// Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Infrastructure;

use App\Modules\Auth\Application\Password\PasswordReset;
use App\Modules\Auth\Application\Ports\PasswordResets;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

final readonly class DatabasePasswordResets implements PasswordResets
{
    public function __construct(private string $key) {}

    public function start(int $userId, string $code, DateTimeImmutable $expiresAt): void
    {
        DB::transaction(function () use ($userId, $code, $expiresAt): void {
            DB::table('password_reset_codes')->where('user_id', $userId)->whereNull('used_at')->update(['used_at' => now()]);
            DB::table('password_reset_codes')->insert([
                'user_id' => $userId,
                'code_hash' => $this->hash($code),
                'expires_at' => $expiresAt,
            ]);
        });
    }

    public function current(int $userId): ?PasswordReset
    {
        $row = DB::table('password_reset_codes')
            ->where('user_id', $userId)
            ->whereNull('used_at')
            ->orderByDesc('id')
            ->first(['id', 'attempts', 'expires_at', 'ticket_expires_at']);

        return $row === null ? null : new PasswordReset(
            (int) $row->id,
            (int) $row->attempts,
            CarbonImmutable::parse((string) $row->expires_at),
            $row->ticket_expires_at === null ? null : CarbonImmutable::parse((string) $row->ticket_expires_at),
        );
    }

    public function matchesCode(PasswordReset $reset, string $code): bool
    {
        return hash_equals((string) $this->column($reset, 'code_hash'), $this->hash($code));
    }

    public function recordFailure(PasswordReset $reset): int
    {
        DB::table('password_reset_codes')->where('id', $reset->id)->increment('attempts');

        return (int) $this->column($reset, 'attempts');
    }

    public function issueTicket(PasswordReset $reset, string $ticket, DateTimeImmutable $expiresAt): void
    {
        DB::table('password_reset_codes')->where('id', $reset->id)->update([
            'ticket_hash' => $this->hash($ticket),
            'ticket_expires_at' => $expiresAt,
        ]);
    }

    public function matchesTicket(PasswordReset $reset, string $ticket): bool
    {
        return hash_equals((string) $this->column($reset, 'ticket_hash'), $this->hash($ticket));
    }

    public function close(PasswordReset $reset): void
    {
        DB::table('password_reset_codes')->where('id', $reset->id)->update(['used_at' => now()]);
    }

    private function hash(string $value): string
    {
        return hash_hmac('sha256', $value, $this->key);
    }

    private function column(PasswordReset $reset, string $column): mixed
    {
        return DB::table('password_reset_codes')->where('id', $reset->id)->value($column);
    }
}
