<?php

declare(strict_types=1);

// Activar o desactivar el modo mantenimiento: { enabled, mfa_code }. Documentado en docs/backend/system.md

namespace App\Modules\System\Http\Requests;

final class MaintenanceRequest extends SystemRequest
{
    public function enabled(): bool
    {
        return $this->boolean('enabled');
    }

    protected function extraRules(): array
    {
        return ['enabled' => ['required', 'boolean']];
    }
}
