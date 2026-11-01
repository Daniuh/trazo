<?php

declare(strict_types=1);

// Caso de uso: editar un rol (nombre, nivel, permisos y política de MFA). Afecta a todas las personas que lo tienen,
// así que cambiar el nivel, tocar permisos sensibles o volver más permisiva la política exige el código de quien
// administra. Las excepciones de sus personas que el cambio vuelve innecesarias se quitan.
// Documentado en docs/backend/users.md

namespace App\Modules\Users\Application;

use App\Modules\Shared\Application\Data\RequestContext;
use App\Modules\Shared\Application\Ports\SessionSignals;
use App\Modules\Shared\Application\Ports\Transaction;
use App\Modules\Users\Application\Data\RoleDetail;
use App\Modules\Users\Application\Data\RoleDraft;
use App\Modules\Users\Application\Ports\AccountSecurity;
use App\Modules\Users\Application\Ports\AdminLog;
use App\Modules\Users\Application\Ports\RoleRepository;
use App\Modules\Users\Domain\Administrator;
use App\Modules\Users\Domain\PermissionChange;
use App\Modules\Users\Domain\RoleAction;

final readonly class UpdateRole
{
    public function __construct(
        private RoleGate $gate,
        private RoleRepository $roles,
        private AccountSecurity $security,
        private AdminLog $log,
        private SessionSignals $signals,
        private Transaction $transaction,
    ) {}

    public function handle(Administrator $admin, string $code, RoleDraft $draft, ?string $mfaCode, RequestContext $context): RoleDetail
    {
        $role = $this->gate->load($admin, $code, RoleAction::Update);
        $change = PermissionChange::between($role->permissions, $role->permissions, $draft->permissions);
        $changes = $this->changes($role, $draft, $change);
        if ($changes === []) {
            return $role;
        }

        if ($draft->level !== $role->role->level) {
            $this->gate->ensureLevel($admin, $draft->level);
        }
        $this->gate->ensureGrantable($admin, $change);
        if ($draft->level !== $role->role->level || $change->isSensitive() || $role->mfa->isRelaxedBy($draft->mfa)) {
            $this->security->confirmIdentity($admin->id, $mfaCode);
        }

        $this->transaction->run(function () use ($admin, $role, $draft, $change, $changes, $context): void {
            $this->roles->update($role->role->id, $draft);
            if (! $change->isEmpty()) {
                $this->roles->pruneMemberOverrides($role->role->id);
            }
            // Las personas de este rol ven sus permisos nuevos sin recargar
            $this->signals->changed(...$this->roles->memberIds($role->role->id));
            $this->log->recordForRole(AdminActionType::RoleUpdated, $admin->id, $role->role->id, [
                'code' => $role->role->code, ...$changes,
            ], $context);
        });

        return $this->gate->reload($code);
    }

    /**
     * Lo que cambia, con valores anteriores y nuevos, para el registro.
     *
     * @return array<string, mixed>
     */
    private function changes(RoleDetail $role, RoleDraft $draft, PermissionChange $change): array
    {
        return array_filter([
            'name' => $role->role->name !== $draft->name ? ['from' => $role->role->name, 'to' => $draft->name] : null,
            'level' => $role->role->level !== $draft->level ? ['from' => $role->role->level, 'to' => $draft->level] : null,
            'permissions' => $change->isEmpty() ? null : ['added' => $change->added, 'removed' => $change->removed],
            'mfa' => $role->mfa->equals($draft->mfa) ? null : [
                'from' => ['required' => $role->mfa->required, 'grace_logins' => $role->mfa->graceLogins],
                'to' => ['required' => $draft->mfa->required, 'grace_logins' => $draft->mfa->graceLogins],
            ],
        ]);
    }
}
