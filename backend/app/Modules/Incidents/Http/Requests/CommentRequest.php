<?php

declare(strict_types=1);

// Validación para escribir en la conversación: un mensaje de 1 a 2.000 caracteres. Leer los anteriores usa
// CommentPageRequest. Documentado en docs/backend/incidents.md

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
        return ['body' => ['required', 'string', 'max:'.self::BODY_MAX]];
    }

    public function body(): string
    {
        return $this->string('body')->trim()->toString();
    }
}
