<?php

declare(strict_types=1);

// Caso de uso: activar o desactivar a una persona. Al desactivarla se cierran sus sesiones en el momento; nunca se
// desactiva al último Super admin activo. Sus incidentes asignados no se reasignan solos: es decisión de quien
// supervisa. Documentado en docs/backend/users.md

namespace App\Modules\Users\Application;

use App\Modules\Shared\Application\Data\RequestContext;
use App\Modules\Shared\Application\Ports\Transaction;
use App\Modules\Users\Application\Data\UserRecord;
use App\Modules\Users\Application\Ports\AccountNotifier;
use App\Modules\Users\Application\Ports\AccountSecurity;
use App\Modules\Users\Application\Ports\AdminLog;
use App\Modules\Users\Application\Ports\UserRepository;
use App\Modules\Users\Domain\Administrator;
use App\Modules\Users\Domain\UserAction;

final readonly class ChangeUserStatus
{
    public function __construct(
        private UserGate $gate,
        private UserRepository $users,
        private RootGuard $roots,
        private AccountSecurity $security,
        private AdminLog $log,
        private AccountNotifier $notifier,
        private Transaction $transaction,
    ) {}

    public function handle(Administrator $admin, string $ulid, bool $active, RequestContext $context): UserRecord
    {
        $user = $this->gate->load($admin, $ulid, $active ? UserAction::Activate : UserAction::Deactivate);

        $this->transaction->run(function () use ($admin, $user, $active, $context): void {
            if (! $active) {
                $this->roots->ensureAnotherRemains($user);
            }

            $this->users->setActive($user->id, $active);
            if (! $active) {
                $this->security->revokeSessions($user->id);
            }

            $type = $active ? AdminActionType::UserActivated : AdminActionType::UserDeactivated;
            $this->log->record($type, $admin->id, $user->id, [], $context);
        });

        $this->notifier->notify($user->id, $active ? AccountEvent::Activated : AccountEvent::Deactivated);

        return $this->gate->reload($user);
    }
}
