<?php

declare(strict_types=1);

// Tipo de widget: número, gráfico o lista. Decide cómo se dibuja en el front. Documentado en docs/backend/dashboard.md

namespace App\Modules\Dashboard\Domain;

enum WidgetKind: string
{
    case Kpi = 'kpi';
    case Chart = 'chart';
    case List = 'list';
}
