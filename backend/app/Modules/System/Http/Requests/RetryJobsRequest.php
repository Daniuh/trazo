<?php

declare(strict_types=1);

// Reintentar jobs fallidos: { ids: [uuid…] } o { all: true }, más el código de reautenticación.
// Documentado en docs/backend/system.md

namespace App\Modules\System\Http\Requests;

final class RetryJobsRequest extends SystemRequest
{
    /**
     * @return list<string>|null null: todos
     */
    public function ids(): ?array
    {
        return $this->boolean('all') ? null : array_values(array_map('strval', (array) $this->input('ids', [])));
    }

    protected function extraRules(): array
    {
        return [
            'all' => ['sometimes', 'boolean'],
            'ids' => ['required_without:all', 'array', 'min:1', 'max:50'],
            'ids.*' => ['string', 'uuid'],
        ];
    }
}
