<?php

declare(strict_types=1);

// Caso de uso: crear una persona con su rol. Nadie le define la contraseña: le llega un correo de bienvenida para
// crearla ella misma. Solo se da un rol por debajo de quien crea; uno con permisos sensibles exige su código.
// Documentado en docs/backend/users.md

namespace App\Modules\Users\Application;

use App\Modules\Shared\Application\AdminActionType;
use App\Modules\Shared\Application\Data\RequestContext;
use App\Modules\Shared\Application\Ports\AdminLog;
use App\Modules\Shared\Application\Ports\Transaction;
use App\Modules\Shared\Domain\Exceptions\ForbiddenException;
use App\Modules\Users\Application\Data\NewUser;
use App\Modules\Users\Application\Data\UserRecord;
use App\Modules\Users\Application\Ports\AccountNotifier;
use App\Modules\Users\Application\Ports\AccountSecurity;
use App\Modules\Users\Application\Ports\RoleCatalog;
use App\Modules\Users\Application\Ports\UserRepository;
use App\Modules\Users\Domain\Administrator;
use App\Modules\Users\Domain\Exceptions\UserExceptions;
use App\Modules\Users\Domain\SensitivePermissions;
use App\Modules\Users\Domain\UserAccess;

final readonly class CreateUser
{
    public function __construct(
        private UserGate $gate,
        private UserAccess $access,
        private UserRepository $users,
        private RoleCatalog $catalog,
        private AccountSecurity $security,
        private AdminLog $log,
        private AccountNotifier $notifier,
        private Transaction $transaction,
    ) {}

    public function handle(Administrator $admin, NewUser $input, ?string $code, RequestContext $context): UserRecord
    {
        $this->gate->ensureCanView($admin);
        if (! $admin->can('users.create')) {
            throw new ForbiddenException;
        }

        $role = $this->catalog->find($input->role);
        if ($role === null || ! $this->access->canGiveRole($admin, $role->level)) {
            throw UserExceptions::roleNotAssignable();
        }
        if (SensitivePermissions::any($this->catalog->permissionsOf($role->id))) {
            $this->security->confirmIdentity($admin->id, $code);
        }

        $user = $this->transaction->run(function () use ($admin, $input, $role, $context): UserRecord {
            $user = $this->users->create($input, $role->id);
            $this->log->record(AdminActionType::UserCreated, $admin->id, $user->id, [
                'name' => $user->name, 'email' => $user->email, 'role' => $role->code,
            ], $context);

            return $user;
        });

        $this->notifier->welcome($user->id);

        return $user;
    }
}
