<?php

declare(strict_types=1);

// Caso de uso: cambiar el rol de una persona. Solo a un rol por debajo de quien lo cambia; si el rol anterior o el
// nuevo traen permisos sensibles, exige su código. La persona queda con los permisos exactos del rol nuevo: sus
// excepciones (concedidos y quitados) se borran y el registro guarda cuáles eran. Documentado en docs/backend/users.md

namespace App\Modules\Users\Application;

use App\Modules\Shared\Application\AdminActionType;
use App\Modules\Shared\Application\Data\RequestContext;
use App\Modules\Shared\Application\Ports\AdminLog;
use App\Modules\Shared\Application\Ports\SessionSignals;
use App\Modules\Shared\Application\Ports\Transaction;
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

        $this->transaction->run(function () use ($admin, $user, $role, $context): void {
            if ($role->level < RoleLevel::ROOT) {
                $this->roots->ensureAnotherRemains($user);
            }

            $this->users->setRole($user->id, $role->id);
            $cleared = $this->clearOverrides($admin, $user);
            $this->log->record(AdminActionType::UserRoleChanged, $admin->id, $user->id, [
                'role' => ['from' => $user->role()?->code, 'to' => $role->code],
                ...($cleared === null ? [] : ['cleared' => $cleared]),
            ], $context);
        });

        $this->permissions->forget($user->id);
        $this->signals->changed($user->id);
        $this->notifier->notify($user->id, AccountEvent::RoleChanged, ['role' => $role->name]);

        return $this->gate->reload($user);
    }

    /**
     * Quita todas las excepciones: nadie arrastra permisos del rol anterior. Devuelve las que había (null si ninguna).
     *
     * @return array{granted: list<string>, denied: list<string>}|null
     */
    private function clearOverrides(Administrator $admin, UserRecord $user): ?array
    {
        $overrides = $this->users->overrides($user->id);
        if ($overrides['grants'] === [] && $overrides['denies'] === []) {
            return null;
        }

        $this->users->replaceOverrides($user->id, [], [], $admin->id);

        return ['granted' => $overrides['grants'], 'denied' => $overrides['denies']];
    }
}
