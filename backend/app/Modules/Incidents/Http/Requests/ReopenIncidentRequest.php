<?php

declare(strict_types=1);

// Validación para reabrir directamente (quien puede aprobar): el motivo y la versión que se conocía.
// Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Http\Requests;

final class ReopenIncidentRequest extends ReopenRequest
{
    use ReadsVersion;

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return ['version' => ['required', 'integer', 'min:1'], ...parent::rules()];
    }
}
