<?php

declare(strict_types=1);

// Validación de la reapertura: pedirla y rechazarla exigen un motivo; reabrir exige motivo y versión, y aprobar solo la
// versión (el motivo es el de la solicitud). Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ReopenRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        $version = ['version' => ['required', 'integer', 'min:1']];
        $reason = ['reason' => ['required', 'string', 'max:'.WorkflowRequest::NOTE_MAX]];

        return match (true) {
            $this->routeIs('incidents.reopen') => [...$version, ...$reason],
            $this->routeIs('incidents.reopen.approve') => $version,
            default => $reason,
        };
    }

    public function version(): int
    {
        return $this->integer('version');
    }

    public function reason(): string
    {
        return $this->string('reason')->trim()->toString();
    }
}
