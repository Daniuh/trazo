<?php

declare(strict_types=1);

// Validación de la conversación: un mensaje de 1 a 2.000 caracteres para escribir, y "before" (el ULID de un mensaje)
// para leer los anteriores. Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CommentRequest extends FormRequest
{
    public const BODY_MAX = 2000;

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return $this->isMethod('POST')
            ? ['body' => ['required', 'string', 'max:'.self::BODY_MAX]]
            : ['before' => ['sometimes', 'string', 'ulid']];
    }

    public function body(): string
    {
        return $this->string('body')->trim()->toString();
    }

    public function before(): ?string
    {
        return $this->filled('before') ? $this->string('before')->lower()->toString() : null;
    }
}
