<?php

declare(strict_types=1);

// Validación de la consulta de métricas: widgets separados por comas y días de la tendencia (7, 30 o 90).
// Documentado en docs/backend/dashboard.md

namespace App\Modules\Dashboard\Http\Requests;

use App\Modules\Dashboard\Domain\WidgetCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class MetricsRequest extends FormRequest
{
    public const TREND_DAYS = [7, 30, 90];

    // Convierte "a,b" en una lista antes de validar cada valor
    protected function prepareForValidation(): void
    {
        $widgets = $this->query('widgets');
        if (is_string($widgets)) {
            $this->merge(['widgets' => array_values(array_filter(explode(',', $widgets)))]);
        }
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'widgets' => ['required', 'array', 'min:1', 'max:20'],
            'widgets.*' => ['string', 'distinct', Rule::enum(WidgetCode::class)],
            'trend_days' => ['sometimes', 'integer', Rule::in(self::TREND_DAYS)],
        ];
    }

    /**
     * @return list<WidgetCode>
     */
    public function codes(): array
    {
        return array_values(array_map(fn (mixed $code): WidgetCode => WidgetCode::from((string) $code), (array) $this->validated('widgets')));
    }

    public function trendDays(): int
    {
        return $this->integer('trend_days', 30);
    }
}
