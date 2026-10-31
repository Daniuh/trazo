<?php

declare(strict_types=1);

// Catálogo de widgets del dashboard, con sus topes en la grilla de 12 columnas y el permiso que exige cada uno.
// Idempotente y no destructivo: agrega los que faltan y nunca pisa los ajustes de los existentes.
// Documentado en docs/base-de-datos/seeders.md

namespace Database\Seeders;

use Database\Seeders\Concerns\InsertsMissingRows;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DashboardWidgetSeeder extends Seeder
{
    use InsertsMissingRows;

    // [código, nombre, descripción, tipo, permiso, min_w, max_w, min_h, max_h, ancho, alto]
    private const WIDGETS = [
        ['total_incidents', 'Total de incidentes', 'Todos los incidentes que puedes ver, en cualquier estado.', 'kpi', null, 2, 6, 2, 3, 3, 2],
        ['pending_incidents', 'Pendientes', 'Incidentes abiertos, en progreso o en espera.', 'kpi', null, 2, 6, 2, 3, 3, 2],
        ['overdue_incidents', 'Vencidos', 'Pendientes cuyo vencimiento ya pasó.', 'kpi', null, 2, 6, 2, 3, 3, 2],
        ['unassigned_incidents', 'Sin asignar', 'Pendientes que nadie ha tomado todavía.', 'kpi', 'incidents.view.unassigned', 2, 6, 2, 3, 3, 2],
        ['incidents_by_status', 'Por estado', 'Cantidad de incidentes en cada estado.', 'chart', null, 4, 12, 3, 6, 6, 4],
        ['incidents_by_priority', 'Por prioridad', 'Cantidad y porcentaje de incidentes por prioridad.', 'chart', null, 3, 12, 3, 6, 6, 4],
        ['created_vs_resolved', 'Creados vs. resueltos', 'Incidentes creados y resueltos cada día.', 'chart', null, 6, 12, 3, 6, 8, 5],
        ['due_soon', 'Próximos a vencer', 'Pendientes que vencen en las próximas 48 horas.', 'list', null, 3, 12, 3, 6, 4, 5],
        ['assigned_to_me', 'Asignados a mí', 'Tus pendientes, del que vence antes al que vence después.', 'list', 'incidents.view.assigned', 3, 12, 3, 6, 6, 5],
        ['workload_by_agent', 'Carga por agente', 'Pendientes y vencidos de cada persona.', 'chart', 'incidents.assign', 4, 12, 3, 8, 6, 5],
    ];

    public function run(): void
    {
        $permissions = DB::table('permissions')->pluck('id', 'code');
        $rows = [];

        foreach (self::WIDGETS as $order => [$code, $name, $description, $kind, $permission, $minW, $maxW, $minH, $maxH, $w, $h]) {
            $rows[] = [
                'code' => $code,
                'name' => $name,
                'description' => $description,
                'kind' => $kind,
                'permission_id' => $permission === null ? null : $permissions[$permission],
                // De diez en diez, para poder intercalar widgets nuevos sin renumerar
                'sort_order' => ($order + 1) * 10,
                'min_w' => $minW,
                'max_w' => $maxW,
                'min_h' => $minH,
                'max_h' => $maxH,
                'default_w' => $w,
                'default_h' => $h,
            ];
        }

        $this->insertMissing('dashboard_widgets', 'code', $rows);
    }
}
