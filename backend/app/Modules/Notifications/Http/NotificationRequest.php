<?php

declare(strict_types=1);

// Validación del centro de notificaciones: la pestaña (conversations o activity), "before" (el ULID de una
// notificación) para leer las anteriores e "incident" (ULID) para marcar como leídas solo las de un incidente.
// Documentado en docs/backend/notifications.md

namespace App\Modules\Notifications\Http;

use App\Modules\Notifications\Domain\NotificationCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class NotificationRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'category' => ['required', 'string', Rule::in(array_column(NotificationCategory::cases(), 'value'))],
            'before' => ['sometimes', 'string', 'ulid'],
            'incident' => ['sometimes', 'string', 'ulid'],
        ];
    }

    public function category(): NotificationCategory
    {
        return NotificationCategory::from($this->string('category')->toString());
    }

    public function before(): ?string
    {
        return $this->filled('before') ? $this->string('before')->lower()->toString() : null;
    }

    public function incident(): ?string
    {
        return $this->filled('incident') ? $this->string('incident')->lower()->toString() : null;
    }
}
