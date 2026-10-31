<?php

declare(strict_types=1);

// Caso de uso: dejar a una persona con exactamente los permisos indicados. Lo que difiere de su rol se guarda como
// excepción (concedido o quitado). Nadie concede lo que no tiene, salvo la cima, y tocar permisos sensibles exige el
// código de quien administra. Documentado en docs/backend/users.md

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
use App\Modules\Users\Application\Ports\UserRepository;
use App\Modules\Users\Domain\Administrator;
use App\Modules\Users\Domain\Exceptions\UserExceptions;
use App\Modules\Users\Domain\PermissionChange;
use App\Modules\Users\Domain\UserAction;

final readonly class ChangeUserPermissions
{
    public function __construct(
        private UserGate $gate,
        private UserRepository $users,
        private UserProfiles $profiles,
        private AccountSecurity $security,
        private EffectivePermissions $permissions,
        private AdminLog $log,
        private AccountNotifier $notifier,
        private SessionSignals $signals,
        private Transaction $transaction,
    ) {}

    /**
     * @param  list<string>  $desired  Los permisos que debe tener (códigos existentes, ya validados)
     */
    public function handle(Administrator $admin, string $ulid, array $desired, ?string $code, RequestContext $context): UserRecord
    {
        $user = $this->gate->load($admin, $ulid, UserAction::GrantPermissions);

        $change = PermissionChange::between($this->profiles->rolePermissions($user), $this->permissions->of($user->id), $desired);
        if ($change->isEmpty()) {
            return $user;
        }

        $notGrantable = $change->notGrantableBy($admin);
        if ($notGrantable !== []) {
            throw UserExceptions::permissionsNotGrantable($notGrantable);
        }
        if ($change->isSensitive()) {
            $this->security->confirmIdentity($admin->id, $code);
        }

        $this->transaction->run(function () use ($admin, $user, $change, $context): void {
            $this->users->replaceOverrides($user->id, $change->grants, $change->denies, $admin->id);
            $this->log->record(AdminActionType::UserPermissionsChanged, $admin->id, $user->id, [
                'added' => $change->added, 'removed' => $change->removed,
            ], $context);
        });

        $this->permissions->forget($user->id);
        $this->signals->changed($user->id);
        $this->notifier->notify($user->id, AccountEvent::PermissionsChanged);

        return $this->gate->reload($user);
    }
}
