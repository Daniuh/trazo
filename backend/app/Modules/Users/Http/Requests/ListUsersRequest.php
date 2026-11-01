<?php

declare(strict_types=1);

// Validación del listado de usuarios: página, tamaño (máximo 100), orden de una lista blanca, búsqueda y filtros.
// Los roles llegan separados por comas: filter[role]=agent,supervisor. Documentado en docs/backend/users.md

namespace App\Modules\Users\Http\Requests;

use App\Modules\Users\Application\Data\UserListCriteria;
use App\Modules\Users\Domain\UserSort;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ListUsersRequest extends FormRequest
{
    use ChecksPermission;

    protected function requiredPermission(): string
    {
        return 'users.view';
    }

    protected function prepareForValidation(): void
    {
        $filters = (array) $this->query('filter', []);
        if (is_string($filters['role'] ?? null)) {
            $filters['role'] = array_values(array_filter(explode(',', $filters['role'])));
        }
        $this->merge(['filter' => $filters]);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $sorts = array_map(fn (UserSort $sort): string => $sort->value, UserSort::cases());

        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
            'sort' => ['sometimes', 'string', Rule::in([...$sorts, ...array_map(fn (string $sort): string => "-{$sort}", $sorts)])],
            'search' => ['sometimes', 'string', 'max:100'],
            'filter' => ['sometimes', 'array'],
            'filter.role' => ['sometimes', 'array', 'max:20'],
            'filter.role.*' => ['string', Rule::exists('roles', 'code')],
            'filter.status' => ['sometimes', 'string', Rule::in(['active', 'inactive'])],
            'filter.mfa' => ['sometimes', 'string', Rule::in(['enabled', 'disabled'])],
        ];
    }

    public function criteria(): UserListCriteria
    {
        $sort = $this->string('sort', UserSort::Name->value)->toString();
        $status = $this->input('filter.status');
        $mfa = $this->input('filter.mfa');

        return new UserListCriteria(
            page: $this->integer('page', 1),
            perPage: $this->integer('per_page', 20),
            sort: UserSort::from(ltrim($sort, '-')),
            descending: str_starts_with($sort, '-'),
            search: $this->filled('search') ? $this->string('search')->trim()->toString() : null,
            roles: array_values(array_map('strval', (array) $this->input('filter.role', []))),
            active: $status === null ? null : $status === 'active',
            mfaEnabled: $mfa === null ? null : $mfa === 'enabled',
        );
    }
}
