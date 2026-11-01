<?php

declare(strict_types=1);

// Casos de uso: restablecer el MFA o el acceso completo de una persona, siempre con el código de quien administra.
// - MFA: quita su factor y sus códigos de recuperación; en el siguiente ingreso lo configura de nuevo.
// - Acceso: su contraseña deja de servir y debe crear una nueva con "¿Olvidaste tu contraseña?".
// En los dos se cierran sus sesiones y se le avisa por correo. Documentado en docs/backend/users.md

namespace App\Modules\Users\Application;

use App\Modules\Shared\Application\AdminActionType;
use App\Modules\Shared\Application\Data\RequestContext;
use App\Modules\Shared\Application\Ports\AdminLog;
use App\Modules\Shared\Application\Ports\SessionSignals;
use App\Modules\Shared\Application\Ports\Transaction;
use App\Modules\Users\Application\Data\UserRecord;
use App\Modules\Users\Application\Ports\AccountNotifier;
use App\Modules\Users\Application\Ports\AccountSecurity;
use App\Modules\Users\Application\Ports\UserRepository;
use App\Modules\Users\Domain\Administrator;
use App\Modules\Users\Domain\UserAction;

final readonly class ResetUserAccess
{
    public function __construct(
        private UserGate $gate,
        private UserRepository $users,
        private AccountSecurity $security,
        private AdminLog $log,
        private AccountNotifier $notifier,
        private SessionSignals $signals,
        private Transaction $transaction,
    ) {}

    public function resetMfa(Administrator $admin, string $ulid, ?string $code, RequestContext $context): UserRecord
    {
        $user = $this->gate->load($admin, $ulid, UserAction::ResetMfa);
        $this->security->confirmIdentity($admin->id, $code);

        $this->transaction->run(function () use ($admin, $user, $context): void {
            $this->security->removeMfa($user->id);
            $this->security->revokeSessions($user->id);
            $this->signals->changed($user->id);
            $this->log->record(AdminActionType::UserMfaReset, $admin->id, $user->id, [], $context);
        });

        $this->notifier->notify($user->id, AccountEvent::MfaReset);

        return $this->gate->reload($user);
    }

    public function resetAccess(Administrator $admin, string $ulid, ?string $code, RequestContext $context): UserRecord
    {
        $user = $this->gate->load($admin, $ulid, UserAction::ResetAccess);
        $this->security->confirmIdentity($admin->id, $code);

        $this->transaction->run(function () use ($admin, $user, $context): void {
            $this->users->scramblePassword($user->id);
            $this->security->revokeSessions($user->id);
            $this->signals->changed($user->id);
            $this->log->record(AdminActionType::UserAccessReset, $admin->id, $user->id, [], $context);
        });

        $this->notifier->notify($user->id, AccountEvent::AccessReset);

        return $this->gate->reload($user);
    }
}
