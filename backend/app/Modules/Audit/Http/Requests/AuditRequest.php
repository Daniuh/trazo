<?php

declare(strict_types=1);

// Lo común de las dos pestañas de la auditoría: rango de fechas, quién hizo el cambio, tipos (separados por comas),
// cursor y tamaño de la tanda. El permiso se revisa antes de validar. Documentado en docs/backend/audit.md

namespace App\Modules\Audit\Http\Requests;

use App\Modules\Audit\Domain\AuditCursor;
use App\Modules\Audit\Domain\AuditPeriod;
use App\Modules\Audit\Http\Rules\Cursor;
use App\Modules\Users\Http\Requests\ChecksPermission;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Foundation\Http\FormRequest;

abstract class AuditRequest extends FormRequest
{
    use ChecksPermission;

    private const DEFAULT_PER_PAGE = 25;

    protected function prepareForValidation(): void
    {
        $filters = (array) $this->query('filter', []);
        if (is_string($filters['type'] ?? null)) {
            $filters['type'] = array_values(array_filter(explode(',', $filters['type'])));
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
            'filter.from' => ['sometimes', 'date'],
            // Solo se compara si llegó el inicio; sin él, el rango se completa hacia atrás
            'filter.to' => ['sometimes', 'date', ...($this->filled('filter.from') ? ['after_or_equal:filter.from'] : [])],
            'filter.actor' => ['sometimes', 'ulid'],
            'filter.type' => ['sometimes', 'array', 'max:20'],
            'filter.type.*' => ['string', 'max:40'],
            ...$this->targetRules(),
        ];
    }

    /**
     * @return array<string, list<mixed>>
     */
    abstract protected function targetRules(): array;

    protected function period(): AuditPeriod
    {
        return AuditPeriod::between($this->utcDate('filter.from'), $this->utcDate('filter.to'), new DateTimeImmutable('now', new DateTimeZone('UTC')));
    }

    protected function cursor(): ?AuditCursor
    {
        return $this->filled('cursor') ? AuditCursor::decode($this->string('cursor')->toString()) : null;
    }

    protected function actor(): ?string
    {
        return $this->filled('filter.actor') ? $this->string('filter.actor')->toString() : null;
    }

    /**
     * @return list<string>
     */
    protected function types(): array
    {
        return array_values(array_map('strval', (array) $this->input('filter.type', [])));
    }

    protected function perPage(): int
    {
        return $this->integer('per_page', self::DEFAULT_PER_PAGE);
    }

    private function utcDate(string $key): ?DateTimeImmutable
    {
        return $this->filled($key)
            ? (new DateTimeImmutable((string) $this->input($key)))->setTimezone(new DateTimeZone('UTC'))
            : null;
    }
}
