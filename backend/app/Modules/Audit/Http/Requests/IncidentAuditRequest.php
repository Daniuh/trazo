<?php

declare(strict_types=1);

// Filtros de la auditoría de incidentes: lo común más el incidente por su código. Documentado en docs/backend/audit.md

namespace App\Modules\Audit\Http\Requests;

use App\Modules\Audit\Application\Data\IncidentAuditCriteria;
use App\Modules\Audit\Domain\AuditAccess;
use App\Modules\Audit\Http\Rules\IncidentCode;

final class IncidentAuditRequest extends AuditRequest
{
    protected function requiredPermission(): string
    {
        return AuditAccess::INCIDENTS;
    }

    protected function targetRules(): array
    {
        return ['filter.incident' => ['sometimes', 'string', 'max:20', new IncidentCode]];
    }

    public function criteria(): IncidentAuditCriteria
    {
        return new IncidentAuditCriteria(
            period: $this->period(),
            actor: $this->actor(),
            types: $this->types(),
            incident: IncidentCode::normalize($this->input('filter.incident')),
            cursor: $this->cursor(),
            perPage: $this->perPage(),
        );
    }
}
