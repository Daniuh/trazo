<?php

declare(strict_types=1);

// Validación del motivo de una reapertura: lo exigen pedirla y rechazarla. Reabrir directamente suma la versión
// (ReopenIncidentRequest) y aprobar solo pide la versión (ApproveReopenRequest). Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReopenRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return ['reason' => ['required', 'string', 'max:'.ChangeStatusRequest::NOTE_MAX]];
    }

    public function reason(): string
    {
        return $this->string('reason')->trim()->toString();
    }
}
