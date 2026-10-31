<?php

declare(strict_types=1);

// Lo que la política de MFA le pide a una persona: si es obligatorio y cuántos ingresos puede posponerlo.
// Con varios roles gana la regla más estricta. Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Domain\Mfa;

final readonly class MfaRequirement
{
    public function __construct(
        public bool $required,
        public int $graceLogins,
    ) {}

    public static function optional(): self
    {
        return new self(false, 0);
    }

    /**
     * La más estricta: obligatorio si algún rol lo exige, y la gracia más corta entre los roles que lo exigen.
     *
     * @param  list<self>  $policies
     */
    public static function strictest(array $policies): self
    {
        $required = array_values(array_filter($policies, fn (self $policy): bool => $policy->required));

        return $required === []
            ? self::optional()
            : new self(true, min(array_map(fn (self $policy): int => $policy->graceLogins, $required)));
    }
}
