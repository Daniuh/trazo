<?php

declare(strict_types=1);

// Decide el paso siguiente del ingreso según si la persona tiene MFA, qué exige su política y cuánta gracia usó.
// Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Domain\Mfa;

final class MfaGate
{
    public static function next(bool $hasFactor, MfaRequirement $requirement, int $graceUsed): LoginStep
    {
        return match (true) {
            $hasFactor => LoginStep::Verify,
            ! $requirement->required => LoginStep::Complete,
            $graceUsed < $requirement->graceLogins => LoginStep::SetupSuggested,
            default => LoginStep::Setup,
        };
    }
}
