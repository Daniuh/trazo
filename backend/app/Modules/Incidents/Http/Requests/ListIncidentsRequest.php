<?php

declare(strict_types=1);

// Validación del listado: página, tamaño (máximo 100), orden y grupo de una lista blanca, búsqueda y filtros.
// Los filtros de varios valores llegan separados por comas: filter[status]=open,in_progress. Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Http\Requests;

use App\Modules\Incidents\Application\Data\IncidentListCriteria;
use App\Modules\Incidents\Domain\IncidentGroup;
use App\Modules\Incidents\Domain\IncidentSort;
use App\Modules\Incidents\Http\Rules\SearchText;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ListIncidentsRequest extends FormRequest
{
    private const LISTS = ['status', 'priority', 'assignee'];

    private const UNASSIGNED = 'none';

    // Convierte "a,b" en una lista antes de validar cada valor
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
        $sorts = array_map(fn (IncidentSort $sort): string => $sort->value, IncidentSort::cases());

        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
            'sort' => ['sometimes', 'string', Rule::in([...$sorts, ...array_map(fn (string $sort): string => "-{$sort}", $sorts)])],
            'search' => ['sometimes', 'string', 'max:100', new SearchText],
            'group_by' => ['sometimes', 'string', Rule::in(array_column(IncidentGroup::cases(), 'value'))],
            'filter' => ['sometimes', 'array'],
            'filter.status' => ['sometimes', 'array', 'max:20'],
            'filter.status.*' => ['string', Rule::exists('statuses', 'code')],
            'filter.priority' => ['sometimes', 'array', 'max:20'],
            'filter.priority.*' => ['string', Rule::exists('priorities', 'code')],
            'filter.assignee' => ['sometimes', 'array', 'max:50'],
            'filter.assignee.*' => ['string', 'regex:/^('.self::UNASSIGNED.'|[0-9a-zA-Z]{26})$/'],
            'filter.creator' => ['sometimes', 'string', 'ulid'],
            'filter.created_from' => ['sometimes', 'date'],
            'filter.created_to' => ['sometimes', 'date'],
            'filter.due_from' => ['sometimes', 'date'],
            'filter.due_to' => ['sometimes', 'date'],
            'filter.overdue' => ['sometimes', 'boolean'],
            'filter.deleted' => ['sometimes', 'boolean'],
            'filter.reopen_requested' => ['sometimes', 'boolean'],
        ];
    }

    public function criteria(): IncidentListCriteria
    {
        $sort = $this->string('sort', '-'.IncidentSort::CreatedAt->value)->toString();
        $assignees = array_map('strtolower', (array) $this->input('filter.assignee', []));

        return new IncidentListCriteria(
            page: $this->integer('page', 1),
            perPage: $this->integer('per_page', 20),
            sort: IncidentSort::from(ltrim($sort, '-')),
            descending: str_starts_with($sort, '-'),
            search: $this->filled('search') ? $this->string('search')->trim()->toString() : null,
            statuses: $this->list('filter.status'),
            priorities: $this->list('filter.priority'),
            assignees: array_values(array_diff($assignees, [self::UNASSIGNED])),
            unassigned: in_array(self::UNASSIGNED, $assignees, true),
            creator: $this->filled('filter.creator') ? strtolower((string) $this->input('filter.creator')) : null,
            createdFrom: $this->utcDate('filter.created_from'),
            createdTo: $this->utcDate('filter.created_to'),
            dueFrom: $this->utcDate('filter.due_from'),
            dueTo: $this->utcDate('filter.due_to'),
            overdue: $this->boolean('filter.overdue'),
            deleted: $this->boolean('filter.deleted'),
            reopenRequested: $this->boolean('filter.reopen_requested'),
            group: $this->filled('group_by') ? IncidentGroup::from($this->string('group_by')->toString()) : null,
        );
    }

    /**
     * @return list<string>
     */
    private function list(string $key): array
    {
        return array_values(array_map('strval', (array) $this->input($key, [])));
    }

    // Las fechas se comparan en UTC, como se guardan
    private function utcDate(string $key): ?DateTimeImmutable
    {
        return $this->filled($key)
            ? (new DateTimeImmutable((string) $this->input($key)))->setTimezone(new DateTimeZone('UTC'))
            : null;
    }
}
