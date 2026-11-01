<?php

declare(strict_types=1);

// Caso de uso: corregir el nombre o el correo de una persona. Si cambia el correo, se avisa a la dirección anterior
// por si no lo esperaba. Documentado en docs/backend/users.md

namespace App\Modules\Users\Application;

use App\Modules\Shared\Application\AdminActionType;
use App\Modules\Shared\Application\Data\RequestContext;
use App\Modules\Shared\Application\Ports\AdminLog;
use App\Modules\Shared\Application\Ports\SessionSignals;
use App\Modules\Shared\Application\Ports\Transaction;
use App\Modules\Users\Application\Data\UserRecord;
use App\Modules\Users\Application\Ports\AccountNotifier;
use App\Modules\Users\Application\Ports\UserRepository;
use App\Modules\Users\Domain\Administrator;
use App\Modules\Users\Domain\UserAction;

final readonly class UpdateUser
{
    public function __construct(
        private UserGate $gate,
        private UserRepository $users,
        private AdminLog $log,
        private AccountNotifier $notifier,
        private SessionSignals $signals,
        private Transaction $transaction,
    ) {}

    public function handle(Administrator $admin, string $ulid, string $name, string $email, RequestContext $context): UserRecord
    {
        $user = $this->gate->load($admin, $ulid, UserAction::Update);

        $changes = array_filter([
            'name' => $user->name !== $name ? ['from' => $user->name, 'to' => $name] : null,
            'email' => $user->email !== $email ? ['from' => $user->email, 'to' => $email] : null,
        ]);
        if ($changes === []) {
            return $user;
        }

        $this->transaction->run(function () use ($admin, $user, $name, $email, $changes, $context): void {
            $this->users->updateProfile($user->id, $name, $email);
            // Su nombre se ve en el encabezado de sus pantallas abiertas
            $this->signals->changed($user->id);
            $this->log->record(AdminActionType::UserUpdated, $admin->id, $user->id, $changes, $context);
        });

        if (isset($changes['email'])) {
            $this->notifier->emailChanged($user->email, $name);
        }

        return $this->gate->reload($user);
    }
}
