<?php

declare(strict_types=1);

// Catálogos del ciclo de vida: prioridades, estados, transiciones y tipos de actividad.
// Idempotente y no destructivo: agrega lo que falta y nunca pisa lo que ya existe.
// Documentado en docs/base-de-datos/seeders.md

namespace Database\Seeders;

use Database\Seeders\Concerns\InsertsMissingRows;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IncidentCatalogSeeder extends Seeder
{
    use InsertsMissingRows;

    private const PRIORITIES = [
        ['code' => 'low', 'name' => 'Baja', 'level' => 1, 'default_resolution_minutes' => 7200],
        ['code' => 'medium', 'name' => 'Media', 'level' => 2, 'default_resolution_minutes' => 4320],
        ['code' => 'high', 'name' => 'Alta', 'level' => 3, 'default_resolution_minutes' => 1440],
        ['code' => 'critical', 'name' => 'Crítica', 'level' => 4, 'default_resolution_minutes' => 240],
    ];

    private const STATUSES = [
        ['code' => 'open', 'name' => 'Abierto', 'sort_order' => 1, 'is_initial' => true, 'is_pending' => true, 'is_final' => false],
        ['code' => 'in_progress', 'name' => 'En progreso', 'sort_order' => 2, 'is_initial' => false, 'is_pending' => true, 'is_final' => false],
        ['code' => 'on_hold', 'name' => 'En espera', 'sort_order' => 3, 'is_initial' => false, 'is_pending' => true, 'is_final' => false],
        ['code' => 'resolved', 'name' => 'Resuelto', 'sort_order' => 4, 'is_initial' => false, 'is_pending' => false, 'is_final' => false],
        ['code' => 'closed', 'name' => 'Cerrado', 'sort_order' => 5, 'is_initial' => false, 'is_pending' => false, 'is_final' => true],
        ['code' => 'cancelled', 'name' => 'Cancelado', 'sort_order' => 6, 'is_initial' => false, 'is_pending' => false, 'is_final' => true],
    ];

    private const TRANSITIONS = [
        'open' => ['in_progress', 'cancelled'],
        'in_progress' => ['on_hold', 'resolved', 'cancelled'],
        'on_hold' => ['in_progress', 'cancelled'],
        'resolved' => ['closed', 'in_progress'],
    ];

    private const ACTIVITY_TYPES = [
        ['code' => 'created', 'name' => 'Creado'],
        ['code' => 'updated', 'name' => 'Editado'],
        ['code' => 'status_changed', 'name' => 'Cambio de estado'],
        ['code' => 'assigned', 'name' => 'Asignado'],
        ['code' => 'unassigned', 'name' => 'Sin asignar'],
        ['code' => 'deleted', 'name' => 'Eliminado'],
        ['code' => 'restored', 'name' => 'Restaurado'],
    ];

    public function run(): void
    {
        $this->insertMissing('priorities', 'code', self::PRIORITIES);
        $this->insertMissing('statuses', 'code', self::STATUSES);
        $this->insertMissing('activity_types', 'code', self::ACTIVITY_TYPES);
        $this->seedTransitions();
    }

    private function seedTransitions(): void
    {
        $ids = DB::table('statuses')->pluck('id', 'code');
        $rows = [];

        foreach (self::TRANSITIONS as $from => $targets) {
            foreach ($targets as $to) {
                $rows[] = ['from_status_id' => $ids[$from], 'to_status_id' => $ids[$to]];
            }
        }

        // Sin autoincremental, aquí INSERT IGNORE no gasta nada
        DB::table('status_transitions')->insertOrIgnore($rows);
    }
}
