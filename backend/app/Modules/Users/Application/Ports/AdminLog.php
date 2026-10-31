<?php

declare(strict_types=1);

// Registro de lo que hace la administración (tabla admin_actions). Documentado en docs/backend/users.md

namespace App\Modules\Users\Application\Ports;

use App\Modules\Shared\Application\Data\RequestContext;
use App\Modules\Users\Application\AdminActionType;

interface AdminLog
{
    /**
     * @param  array<string, mixed>  $details  Lo que cambió, con valores anteriores y nuevos
     */
    public function record(AdminActionType $type, int $adminId, ?int $targetUserId, array $details, RequestContext $context): void;

    /**
     * Una acción sobre un rol (crearlo, editarlo o eliminarlo).
     *
     * @param  array<string, mixed>  $details
     */
    public function recordForRole(AdminActionType $type, int $adminId, ?int $roleId, array $details, RequestContext $context): void;
}
