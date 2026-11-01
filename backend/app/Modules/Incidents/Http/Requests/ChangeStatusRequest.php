<?php

declare(strict_types=1);

// Validación para cambiar el estado: el estado nuevo, la nota (obligatoria según el estado, lo decide el dominio) y la
// versión que se conocía. Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ChangeStatusRequest extends FormRequest
{
    use ReadsVersion;

    public const NOTE_MAX = 1000;

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'version' => ['required', 'integer', 'min:1'],
            'status' => ['required', 'string', 'max:30'],
            'note' => ['nullable', 'string', 'max:'.self::NOTE_MAX],
        ];
    }

    public function status(): string
    {
        return $this->string('status')->toString();
    }

    // Una nota con solo espacios cuenta como vacía
    public function note(): ?string
    {
        $note = $this->string('note')->trim()->toString();

        return $note === '' ? null : $note;
    }
}
