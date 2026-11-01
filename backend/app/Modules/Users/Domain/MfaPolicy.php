<?php

declare(strict_types=1);

// Política de MFA de un rol: si es obligatoria y en cuántos ingresos se puede posponer. Dice si un cambio la vuelve
// más permisiva, lo que exige que quien administra confirme con su código. Documentado en docs/backend/users.md

namespace App\Modules\Users\Domain;

final readonly class MfaPolicy
{
    public const MAX_GRACE_LOGINS = 5;

    public function __construct(
        public bool $required,
        public int $graceLogins,
    ) {}

    // Deja de ser obligatoria, o da más ingresos para posponerla
    public function isRelaxedBy(self $next): bool
    {
        return ($this->required && ! $next->required) || $next->graceLogins > $this->graceLogins;
    }

    public function equals(self $other): bool
    {
        return $this->required === $other->required && $this->graceLogins === $other->graceLogins;
    }
}
