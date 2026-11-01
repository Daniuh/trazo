<?php

declare(strict_types=1);

// Filtros de la auditoría de administración: lo común más la persona (ULID) o el rol (código) afectado.
// Documentado en docs/backend/audit.md

namespace App\Modules\Audit\Http\Requests;

use App\Modules\Audit\Application\Data\AdminAuditCriteria;
use App\Modules\Audit\Domain\AuditAccess;
use App\Modules\Users\Http\Rules\RoleCode;

final class AdminAuditRequest extends AuditRequest
{
    protected function requiredPermission(): string
    {
        return AuditAccess::ADMIN;
    }

    protected function targetRules(): array
    {
        return [
            'filter.user' => ['sometimes', 'ulid'],
            'filter.role' => ['sometimes', 'string', 'max:40', new RoleCode],
        ];
    }

    public function criteria(): AdminAuditCriteria
    {
        return new AdminAuditCriteria(
            period: $this->period(),
            actor: $this->actor(),
            types: $this->types(),
            user: $this->filled('filter.user') ? $this->string('filter.user')->toString() : null,
            role: $this->filled('filter.role') ? $this->string('filter.role')->toString() : null,
            cursor: $this->cursor(),
            perPage: $this->perPage(),
        );
    }
}
