<?php

declare(strict_types=1);

// Caso de uso: cambiar el rol de una persona. Solo a un rol por debajo de quien lo cambia; si el rol anterior o el
// nuevo traen permisos sensibles, exige su código. Las excepciones de permisos que el rol nuevo vuelve innecesarias
// se quitan, y la cima de la jerarquía no lleva ninguna. Documentado en docs/backend/users.md

namespace App\Modules\Users\Application;

use App\Modules\Shared\Application\AdminActionType;
use App\Modules\Shared\Application\Data\RequestContext;
use App\Modules\Shared\Application\Ports\AdminLog;
use App\Modules\Shared\Application\Ports\SessionSignals;
use App\Modules\Shared\Application\Ports\Transaction;
use App\Modules\Users\Application\Data\RoleInfo;
use App\Modules\Users\Application\Data\UserRecord;
use App\Modules\Users\Application\Ports\AccountNotifier;
use App\Modules\Users\Application\Ports\AccountSecurity;
use App\Modules\Users\Application\Ports\EffectivePermissions;
use App\Modules\Users\Application\Ports\RoleCatalog;
use App\Modules\Users\Application\Ports\UserRepository;
use App\Modules\Users\Domain\Administrator;
use App\Modules\Users\Domain\Exceptions\UserExceptions;
use App\Modules\Users\Domain\RoleLevel;
use App\Modules\Users\Domain\SensitivePermissions;
use App\Modules\Users\Domain\UserAccess;
use App\Modules\Users\Domain\UserAction;

final readonly class ChangeUserRole
{
    public function __construct(
        private UserGate $gate,
        private UserAccess $access,
        private UserRepository $users,
        private UserProfiles $profiles,
        private RoleCatalog $catalog,
        private RootGuard $roots,
        private AccountSecurity $security,
        private EffectivePermissions $permissions,
        private AdminLog $log,
        private AccountNotifier $notifier,
        private SessionSignals $signals,
        private Transaction $transaction,
    ) {}

    public function handle(Administrator $admin, string $ulid, string $roleCode, ?string $code, RequestContext $context): UserRecord
    {
        $user = $this->gate->load($admin, $ulid, UserAction::AssignRole);

        $role = $this->catalog->find($roleCode);
        if ($role === null || ! $this->access->canGiveRole($admin, $role->level)) {
            throw UserExceptions::roleNotAssignable();
        }
        if ($user->role()?->code === $role->code && count($user->roles) === 1) {
            return $user;
        }

        $previous = $this->profiles->rolePermissions($user);
        $next = $this->catalog->permissionsOf($role->id);
        if (SensitivePermissions::any([...$previous, ...$next])) {
            $this->security->confirmIdentity($admin->id, $code);
        }

        $this->transaction->run(function () use ($admin, $user, $role, $next, $context): void {
            if ($role->level < RoleLevel::ROOT) {
                $this->roots->ensureAnotherRemains($user);
            }

            $this->users->setRole($user->id, $role->id);
            $this->pruneOverrides($admin, $user, $role, $next);
            $this->log->record(AdminActionType::UserRoleChanged, $admin->id, $user->id, [
                'role' => ['from' => $user->role()?->code, 'to' => $role->code],
            ], $context);
        });

        $this->permissions->forget($user->id);
        $this->signals->changed($user->id);
        $this->notifier->notify($user->id, AccountEvent::RoleChanged, ['role' => $role->name]);

        return $this->gate->reload($user);
    }

    /**
     * Un concedido que el rol nuevo ya trae, o un quitado que ya no trae, dejan de tener sentido.
     *
     * @param  list<string>  $rolePermissions
     */
    private function pruneOverrides(Administrator $admin, UserRecord $user, RoleInfo $role, array $rolePermissions): void
    {
        $overrides = $this->users->overrides($user->id);
        $root = $role->level >= RoleLevel::ROOT;

        $this->users->replaceOverrides(
            $user->id,
            $root ? [] : array_values(array_diff($overrides['grants'], $rolePermissions)),
            $root ? [] : array_values(array_intersect($overrides['denies'], $rolePermissions)),
            $admin->id,
        );
    }
}
