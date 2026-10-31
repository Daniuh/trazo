<?php

declare(strict_types=1);

// Diseño guardado de cada persona. Sin filas, la persona ve el diseño original. Documentado en docs/backend/dashboard.md

namespace App\Modules\Dashboard\Application\Ports;

use App\Modules\Dashboard\Domain\WidgetLayout;

interface LayoutStore
{
    /**
     * @return array<string, WidgetLayout>|null Por código de widget; null si nunca lo personalizó
     */
    public function saved(int $userId): ?array;

    /**
     * @param  list<WidgetLayout>  $layouts
     */
    public function replace(int $userId, array $layouts): void;

    public function clear(int $userId): void;
}
