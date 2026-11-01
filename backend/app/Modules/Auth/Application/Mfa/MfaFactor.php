<?php

declare(strict_types=1);

// El factor TOTP de una persona: su secreto (ya descifrado), si lo confirmó y el último paso de tiempo usado.
// Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Application\Mfa;

final readonly class MfaFactor
{
    public function __construct(
        public string $secret,
        public bool $confirmed,
        public ?int $lastUsedStep,
    ) {}
}
