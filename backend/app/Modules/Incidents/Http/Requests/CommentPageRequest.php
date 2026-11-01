<?php

declare(strict_types=1);

// Validación para leer la conversación: "before" (el ULID de un mensaje) pide la página de mensajes anteriores.
// Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CommentPageRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return ['before' => ['sometimes', 'string', 'ulid']];
    }

    public function before(): ?string
    {
        return $this->filled('before') ? $this->string('before')->lower()->toString() : null;
    }
}
