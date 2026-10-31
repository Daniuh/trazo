<?php

declare(strict_types=1);

// Guarda cada acción administrativa en admin_actions, con su tipo del catálogo. Documentado en docs/backend/users.md

namespace App\Modules\Users\Infrastructure;

use App\Modules\Shared\Application\Data\RequestContext;
use App\Modules\Users\Application\AdminActionType;
use App\Modules\Users\Application\Ports\AdminLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;

final class DatabaseAdminLog implements AdminLog
{
    /** @var array<string, int>|null */
    private ?array $types = null;

    public function record(AdminActionType $type, int $adminId, ?int $targetUserId, array $details, RequestContext $context): void
    {
        DB::table('admin_actions')->insert([
            'user_id' => $adminId,
            'admin_action_type_id' => $this->typeId($type),
            'target_user_id' => $targetUserId,
            'details' => $details === [] ? null : json_encode($details, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'ip_address' => $context->ip,
            'user_agent' => $context->userAgent === null ? null : Str::limit($context->userAgent, 252),
        ]);
    }

    private function typeId(AdminActionType $type): int
    {
        $this->types ??= DB::table('admin_action_types')->pluck('id', 'code')->map(fn (mixed $id): int => (int) $id)->all();

        return $this->types[$type->value] ?? throw new LogicException("Falta el tipo de acción {$type->value} en el catálogo");
    }
}
