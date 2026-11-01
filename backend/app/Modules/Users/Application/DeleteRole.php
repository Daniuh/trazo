<?php

declare(strict_types=1);

// Caso de uso: eliminar un rol creado por la administración. Los roles base no se eliminan, y uno que alguna persona
// tiene tampoco. El registro guarda su código y su nombre. Documentado en docs/backend/users.md

namespace App\Modules\Users\Application;

use App\Modules\Shared\Application\AdminActionType;
use App\Modules\Shared\Application\Data\RequestContext;
use App\Modules\Shared\Application\Ports\AdminLog;
use App\Modules\Shared\Application\Ports\Transaction;
use App\Modules\Users\Application\Ports\RoleRepository;
use App\Modules\Users\Domain\Administrator;
use App\Modules\Users\Domain\RoleAction;

final readonly class DeleteRole
{
    public function __construct(
        private RoleGate $gate,
        private RoleRepository $roles,
        private AdminLog $log,
        private Transaction $transaction,
    ) {}

    public function handle(Administrator $admin, string $code, RequestContext $context): void
    {
        $role = $this->gate->load($admin, $code, RoleAction::Delete);

        $this->transaction->run(function () use ($admin, $role, $context): void {
            $this->roles->delete($role->role->id);
            $this->log->recordForRole(AdminActionType::RoleDeleted, $admin->id, null, [
                'code' => $role->role->code, 'name' => $role->role->name, 'level' => $role->role->level,
            ], $context);
        });
    }
}
