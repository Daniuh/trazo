<?php

declare(strict_types=1);

// Catálogo de widgets desde dashboard_widgets, con el código del permiso que exige cada uno.
// Se lee una vez por petición. Documentado en docs/backend/dashboard.md

namespace App\Modules\Dashboard\Infrastructure;

use App\Modules\Dashboard\Application\Ports\WidgetCatalog;
use App\Modules\Dashboard\Domain\WidgetCode;
use App\Modules\Dashboard\Domain\WidgetDefinition;
use App\Modules\Dashboard\Domain\WidgetKind;
use Illuminate\Support\Facades\DB;

final class DatabaseWidgetCatalog implements WidgetCatalog
{
    /** @var list<WidgetDefinition>|null */
    private ?array $active = null;

    public function active(): array
    {
        return $this->active ??= $this->load();
    }

    /**
     * @return list<WidgetDefinition>
     */
    private function load(): array
    {
        $rows = DB::table('dashboard_widgets')
            ->leftJoin('permissions', 'permissions.id', '=', 'dashboard_widgets.permission_id')
            ->where('dashboard_widgets.is_active', true)
            ->orderBy('dashboard_widgets.sort_order')
            ->orderBy('dashboard_widgets.id')
            ->get(['dashboard_widgets.*', 'permissions.code AS permission_code']);

        $definitions = [];
        foreach ($rows as $row) {
            // Una fila sin implementación en el código no se ofrece
            $code = WidgetCode::tryFrom((string) $row->code);
            $kind = WidgetKind::tryFrom((string) $row->kind);
            if ($code === null || $kind === null) {
                continue;
            }

            $definitions[] = new WidgetDefinition(
                $code,
                (string) $row->name,
                (string) $row->description,
                $kind,
                $row->permission_code === null ? null : (string) $row->permission_code,
                (int) $row->min_w,
                (int) $row->max_w,
                (int) $row->min_h,
                (int) $row->max_h,
                (int) $row->default_w,
                (int) $row->default_h,
                (bool) $row->in_default_layout,
            );
        }

        return $definitions;
    }
}
