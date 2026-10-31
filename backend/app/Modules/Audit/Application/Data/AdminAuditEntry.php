<?php

declare(strict_types=1);

// Una acción administrativa en la auditoría: qué, quién, cuándo, sobre quién o sobre qué rol y desde dónde.
// Un rol eliminado ya no existe: su código y su nombre salen del detalle. Documentado en docs/backend/audit.md

namespace App\Modules\Audit\Application\Data;

use DateTimeImmutable;

final readonly class AdminAuditEntry
{
    /**
     * @param  array<string, mixed>|null  $details
     */
    public function __construct(
        public int $id,
        public DateTimeImmutable $createdAt,
        public string $typeCode,
        public string $typeName,
        public PersonRef $actor,
        public ?PersonRef $targetUser,
        public ?RoleRef $targetRole,
        public ?array $details,
        public ?string $ipAddress,
        public ?string $userAgent,
    ) {}
}
