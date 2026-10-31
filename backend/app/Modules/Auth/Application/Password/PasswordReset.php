<?php

declare(strict_types=1);

// Una solicitud de recuperación vigente: cuándo vence el código, los intentos que lleva y, si ya se verificó, hasta
// cuándo vale su pase. Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Application\Password;

use DateTimeImmutable;

final readonly class PasswordReset
{
    public function __construct(
        public int $id,
        public int $attempts,
        public DateTimeImmutable $expiresAt,
        public ?DateTimeImmutable $ticketExpiresAt,
    ) {}

    public function codeIsUsable(DateTimeImmutable $now, int $maxAttempts): bool
    {
        return $this->ticketExpiresAt === null && $this->expiresAt > $now && $this->attempts < $maxAttempts;
    }

    public function ticketIsUsable(DateTimeImmutable $now): bool
    {
        return $this->ticketExpiresAt !== null && $this->ticketExpiresAt > $now;
    }
}
