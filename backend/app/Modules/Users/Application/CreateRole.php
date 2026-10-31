<?php

declare(strict_types=1);

// Caso de uso: crear un rol con su nivel, sus permisos y su política de MFA. Solo por debajo del nivel de quien lo
// crea, sin permisos que no tenga; si trae permisos sensibles, exige su código. Documentado en docs/backend/users.md

namespace App\Modules\Users\Application;

use App\Modules\Shared\Application\Data\RequestContext;
use App\Modules\Shared\Application\Ports\Transaction;
use App\Modules\Shared\Domain\Exceptions\ForbiddenException;
use App\Modules\Users\Application\Data\RoleDetail;
use App\Modules\Users\Application\Data\RoleDraft;
use App\Modules\Users\Application\Ports\AccountSecurity;
use App\Modules\Users\Application\Ports\AdminLog;
use App\Modules\Users\Application\Ports\RoleRepository;
use App\Modules\Users\Domain\Administrator;
use App\Modules\Users\Domain\PermissionChange;
use App\Modules\Users\Domain\RoleAccess;
use App\Modules\Users\Domain\SensitivePermissions;

final readonly class CreateRole
{
    public function __construct(
        private RoleGate $gate,
        private RoleAccess $access,
        private RoleRepository $roles,
        private AccountSecurity $security,
        private AdminLog $log,
        private Transaction $transaction,
    ) {}

    public function handle(Administrator $admin, string $code, RoleDraft $draft, ?string $mfaCode, RequestContext $context): RoleDetail
    {
        if (! $this->access->canCreate($admin)) {
            throw new ForbiddenException;
        }
        $this->gate->ensureLevel($admin, $draft->level);
        $this->gate->ensureGrantable($admin, PermissionChange::between([], [], $draft->permissions));
        if (SensitivePermissions::any($draft->permissions)) {
            $this->security->confirmIdentity($admin->id, $mfaCode);
        }

        $this->transaction->run(function () use ($admin, $code, $draft, $context): void {
            $id = $this->roles->create($code, $draft);
            $this->log->recordForRole(AdminActionType::RoleCreated, $admin->id, $id, [
                'code' => $code, 'name' => $draft->name, 'level' => $draft->level, 'permissions' => $draft->permissions,
                'mfa' => ['required' => $draft->mfa->required, 'grace_logins' => $draft->mfa->graceLogins],
            ], $context);
        });

        return $this->gate->reload($code);
    }
}
