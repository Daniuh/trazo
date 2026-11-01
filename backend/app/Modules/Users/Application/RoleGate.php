<?php

declare(strict_types=1);

// Carga un rol solo si quien administra puede hacer la acción pedida, y revisa lo común a crear y editar: el nivel
// permitido y que nadie agregue permisos que no tiene. Documentado en docs/backend/users.md

namespace App\Modules\Users\Application;

use App\Modules\Shared\Domain\Exceptions\ForbiddenException;
use App\Modules\Users\Application\Data\RoleDetail;
use App\Modules\Users\Application\Ports\RoleRepository;
use App\Modules\Users\Domain\Administrator;
use App\Modules\Users\Domain\Exceptions\RoleExceptions;
use App\Modules\Users\Domain\Exceptions\UserExceptions;
use App\Modules\Users\Domain\PermissionChange;
use App\Modules\Users\Domain\RoleAccess;
use App\Modules\Users\Domain\RoleAction;

final readonly class RoleGate
{
    public function __construct(
        private RoleRepository $roles,
        private RoleAccess $access,
    ) {}

    // Ver los roles: quien administra personas (para elegir uno) o quien edita roles
    public function ensureCanView(Administrator $admin): void
    {
        if (! $admin->can('users.view') && ! $admin->can(RoleAccess::PERMISSION)) {
            throw new ForbiddenException;
        }
    }

    public function load(Administrator $admin, string $code, ?RoleAction $action = null): RoleDetail
    {
        $this->ensureCanView($admin);
        $role = $this->roles->find($code) ?? throw RoleExceptions::notFound();

        if ($action !== null) {
            $denial = $this->access->denial($action, $admin, $role->managed());
            if ($denial !== null) {
                throw RoleExceptions::denied($denial);
            }
        }

        return $role;
    }

    public function ensureLevel(Administrator $admin, int $level): void
    {
        if (! $this->access->canUseLevel($admin, $level)) {
            throw RoleExceptions::levelNotAllowed($admin->isRoot() ? RoleAccess::MAX_LEVEL : $admin->level - 1);
        }
    }

    public function ensureGrantable(Administrator $admin, PermissionChange $change): void
    {
        $notGrantable = $change->notGrantableBy($admin);
        if ($notGrantable !== []) {
            throw UserExceptions::permissionsNotGrantable($notGrantable);
        }
    }

    // Recargar después de guardar, para responder con lo que quedó
    public function reload(string $code): RoleDetail
    {
        return $this->roles->find($code) ?? throw RoleExceptions::notFound();
    }
}
