<?php

declare(strict_types=1);

// Validación para aprobar una solicitud de reapertura: solo la versión que se conocía (el motivo es el de la
// solicitud). Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ApproveReopenRequest extends FormRequest
{
    use ReadsVersion;

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return ['version' => ['required', 'integer', 'min:1']];
    }
}
