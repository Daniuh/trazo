<?php

declare(strict_types=1);

// Validación del diseño enviado: forma y tipos. Los topes, los permisos y los choques los valida el dominio.
// Documentado en docs/backend/dashboard.md

namespace App\Modules\Dashboard\Http\Requests;

use App\Modules\Dashboard\Domain\DashboardGrid;
use App\Modules\Dashboard\Domain\Placement;
use App\Modules\Dashboard\Domain\WidgetCode;
use App\Modules\Dashboard\Domain\WidgetLayout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SaveLayoutRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $columns = DashboardGrid::COLUMNS;

        return [
            'widgets' => ['present', 'array', 'max:50'],
            'widgets.*' => ['array:code,x,y,w,h,visible'],
            'widgets.*.code' => ['required', 'string', 'distinct', Rule::enum(WidgetCode::class)],
            'widgets.*.x' => ['required', 'integer', 'between:0,'.($columns - 1)],
            'widgets.*.y' => ['required', 'integer', 'between:0,'.DashboardGrid::MAX_ROW],
            'widgets.*.w' => ['required', 'integer', 'between:1,'.$columns],
            'widgets.*.h' => ['required', 'integer', 'between:1,50'],
            'widgets.*.visible' => ['required', 'boolean'],
        ];
    }

    /**
     * @return list<WidgetLayout>
     */
    public function layouts(): array
    {
        /** @var list<array{code: string, x: int, y: int, w: int, h: int, visible: bool}> $widgets */
        $widgets = $this->validated('widgets');

        return array_map(fn (array $widget): WidgetLayout => new WidgetLayout(
            WidgetCode::from($widget['code']),
            new Placement((int) $widget['x'], (int) $widget['y'], (int) $widget['w'], (int) $widget['h']),
            (bool) $widget['visible'],
        ), $widgets);
    }
}
