<?php

declare(strict_types=1);

// La política de MFA que aplica a cada persona según sus roles, y los ingresos de gracia que ya usó.
// Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Application\Ports;

use App\Modules\Auth\Domain\Mfa\MfaRequirement;

interface MfaPolicies
{
    public function requirementFor(int $userId): MfaRequirement;

    public function graceUsed(int $userId): int;

    public function useGrace(int $userId): void;
}
