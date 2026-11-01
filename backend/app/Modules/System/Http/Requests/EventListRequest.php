<?php

declare(strict_types=1);

// Lista de eventos: filtros por estado, severidad y tipo (separados por comas), cursor y tamaño de la tanda.
// Documentado en docs/backend/system.md

namespace App\Modules\System\Http\Requests;

use App\Modules\Shared\Domain\TimeCursor;
use App\Modules\Shared\Http\Rules\Cursor;
use App\Modules\System\Application\Data\EventCriteria;
use App\Modules\System\Domain\EventStatus;
use App\Modules\System\Domain\Operator;
use App\Modules\Users\Http\Requests\ChecksPermission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class EventListRequest extends FormRequest
{
    use ChecksPermission;

    private const LISTS = ['status', 'severity', 'type'];

    private const SEVERITIES = ['info', 'warning', 'critical'];

    protected function prepareForValidation(): void
    {
        $filters = (array) $this->query('filter', []);
        foreach (self::LISTS as $key) {
            if (is_string($filters[$key] ?? null)) {
                $filters[$key] = array_values(array_filter(explode(',', $filters[$key])));
            }
        }
        $this->merge(['filter' => $filters]);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
            'cursor' => ['sometimes', 'string', 'max:100', new Cursor],
            'filter' => ['sometimes', 'array'],
            'filter.status' => ['sometimes', 'array', 'max:3'],
            'filter.status.*' => ['string', Rule::enum(EventStatus::class)],
            'filter.severity' => ['sometimes', 'array', 'max:3'],
            'filter.severity.*' => ['string', Rule::in(self::SEVERITIES)],
            'filter.type' => ['sometimes', 'array', 'max:20'],
            'filter.type.*' => ['string', Rule::exists('system_event_types', 'code')],
        ];
    }

    public function criteria(): EventCriteria
    {
        return new EventCriteria(
            $this->list('filter.status'),
            $this->list('filter.severity'),
            $this->list('filter.type'),
            $this->filled('cursor') ? TimeCursor::decode($this->string('cursor')->toString()) : null,
            $this->integer('per_page', 25),
        );
    }

    protected function requiredPermission(): string
    {
        return Operator::LOGS;
    }

    /**
     * @return list<string>
     */
    private function list(string $key): array
    {
        return array_values(array_map('strval', (array) $this->input($key, [])));
    }
}
