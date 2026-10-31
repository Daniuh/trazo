<?php

declare(strict_types=1);

// Forma JSON del dashboard de una persona: alcance, si está personalizado y cada widget con sus topes y su lugar.
// Documentado en docs/backend/dashboard.md

namespace App\Modules\Dashboard\Http;

use App\Modules\Dashboard\Application\Data\DashboardView;
use App\Modules\Dashboard\Application\Data\WidgetView;
use App\Modules\Dashboard\Domain\DashboardGrid;

final class DashboardResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(DashboardView $view): array
    {
        return [
            'scope' => $view->general ? 'general' : 'personal',
            'customized' => $view->customized,
            'columns' => DashboardGrid::COLUMNS,
            'widgets' => array_map(fn (WidgetView $widget): array => [
                'code' => $widget->definition->code->value,
                'name' => $widget->definition->name,
                'description' => $widget->definition->description,
                'kind' => $widget->definition->kind->value,
                'limits' => [
                    'min_w' => $widget->definition->minW,
                    'max_w' => $widget->definition->maxW,
                    'min_h' => $widget->definition->minH,
                    'max_h' => $widget->definition->maxH,
                ],
                'visible' => $widget->layout->visible,
                'x' => $widget->layout->placement->x,
                'y' => $widget->layout->placement->y,
                'w' => $widget->layout->placement->w,
                'h' => $widget->layout->placement->h,
            ], $view->widgets),
        ];
    }
}
