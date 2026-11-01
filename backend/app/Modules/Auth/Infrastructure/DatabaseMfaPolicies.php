<?php

declare(strict_types=1);

// Lee la política de MFA de los roles de una persona (mfa_policies) y su contador de ingresos pospuestos.
// Un rol sin fila de política no exige MFA. Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Infrastructure;

use App\Modules\Auth\Application\Ports\MfaPolicies;
use App\Modules\Auth\Domain\Mfa\MfaRequirement;
use Illuminate\Support\Facades\DB;

final class DatabaseMfaPolicies implements MfaPolicies
{
    public function requirementFor(int $userId): MfaRequirement
    {
        $policies = array_values(DB::table('user_role')
            ->join('mfa_policies', 'mfa_policies.role_id', '=', 'user_role.role_id')
            ->where('user_role.user_id', $userId)
            ->get(['mfa_policies.is_required', 'mfa_policies.grace_logins'])
            ->map(fn (object $row): MfaRequirement => new MfaRequirement((bool) $row->is_required, (int) $row->grace_logins))
            ->all());

        return MfaRequirement::strictest($policies);
    }

    public function graceUsed(int $userId): int
    {
        return (int) DB::table('users')->where('id', $userId)->value('mfa_grace_used');
    }

    public function useGrace(int $userId): void
    {
        DB::table('users')->where('id', $userId)->increment('mfa_grace_used');
    }
}
