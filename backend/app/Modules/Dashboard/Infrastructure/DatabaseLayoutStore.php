<?php

declare(strict_types=1);

// Diseño de cada persona en user_dashboard_widgets. Guardar reemplaza el diseño completo en una transacción,
// así nunca queda a medias. Documentado en docs/backend/dashboard.md

namespace App\Modules\Dashboard\Infrastructure;

use App\Modules\Dashboard\Application\Ports\LayoutStore;
use App\Modules\Dashboard\Domain\Placement;
use App\Modules\Dashboard\Domain\WidgetCode;
use App\Modules\Dashboard\Domain\WidgetLayout;
use Illuminate\Support\Facades\DB;

final class DatabaseLayoutStore implements LayoutStore
{
    public function saved(int $userId): ?array
    {
        $rows = DB::table('user_dashboard_widgets')
            ->join('dashboard_widgets', 'dashboard_widgets.id', '=', 'user_dashboard_widgets.dashboard_widget_id')
            ->where('user_dashboard_widgets.user_id', $userId)
            ->get(['dashboard_widgets.code', 'user_dashboard_widgets.x', 'user_dashboard_widgets.y', 'user_dashboard_widgets.w', 'user_dashboard_widgets.h', 'user_dashboard_widgets.is_visible']);

        if ($rows->isEmpty()) {
            return null;
        }

        $layouts = [];
        foreach ($rows as $row) {
            $code = WidgetCode::tryFrom((string) $row->code);
            if ($code !== null) {
                $placement = new Placement((int) $row->x, (int) $row->y, (int) $row->w, (int) $row->h);
                $layouts[$code->value] = new WidgetLayout($code, $placement, (bool) $row->is_visible);
            }
        }

        return $layouts;
    }

    public function replace(int $userId, array $layouts): void
    {
        $ids = DB::table('dashboard_widgets')->pluck('id', 'code');
        $now = now();

        $rows = array_map(fn (WidgetLayout $layout): array => [
            'user_id' => $userId,
            'dashboard_widget_id' => $ids[$layout->code->value],
            'x' => $layout->placement->x,
            'y' => $layout->placement->y,
            'w' => $layout->placement->w,
            'h' => $layout->placement->h,
            'is_visible' => $layout->visible,
            'created_at' => $now,
            'updated_at' => $now,
        ], $layouts);

        DB::transaction(function () use ($userId, $rows): void {
            // Dos guardados a la vez de la misma persona se hacen uno detrás del otro
            DB::table('users')->where('id', $userId)->lockForUpdate()->value('id');
            $this->clear($userId);
            DB::table('user_dashboard_widgets')->insert($rows);
        });
    }

    public function clear(int $userId): void
    {
        DB::table('user_dashboard_widgets')->where('user_id', $userId)->delete();
    }
}
