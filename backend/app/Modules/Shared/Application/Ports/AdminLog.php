<?php

declare(strict_types=1);

// Registro de lo que hace la administración (tabla admin_actions). Documentado en docs/backend/shared.md

namespace App\Modules\Shared\Application\Ports;

use App\Modules\Shared\Application\AdminActionType;
use App\Modules\Shared\Application\Data\RequestContext;

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
