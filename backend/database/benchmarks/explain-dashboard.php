<?php

declare(strict_types=1);

// Mide las consultas del dashboard: tiempo de cada fuente sin caché y plan de MySQL (EXPLAIN) de cada consulta,
// para quien ve todo, un agente y un solicitante. Los pasos están en docs/base-de-datos/rendimiento.md

use App\Modules\Dashboard\Application\Ports\DashboardQueries;
use App\Modules\Incidents\Domain\VisibilityScope as V;
use Illuminate\Support\Facades\DB;

$q = app(DashboardQueries::class);
$agent = (int) DB::table('users')->where('email', 'agente1@trazo.test')->value('id');
$req = (int) DB::table('users')->where('email', 'solicitante1@trazo.test')->value('id');
$pending = DB::table('statuses')->where('is_pending', true)->pluck('id')->map(fn (mixed $id): int => (int) $id)->all();
$now = new DateTimeImmutable;
$scopes = [
    'todo' => [$agent, new V(true, false, false, false)],
    'agente' => [$agent, new V(false, true, true, true)],
    'solicitante' => [$req, new V(false, true, false, false)],
];
$sources = [
    'por estado y prioridad' => fn (int $id, V $s) => $q->countsByStatusAndPriority($id, $s),
    'vencidos' => fn (int $id, V $s) => $q->overdueCount($id, $s, $pending, $now),
    'sin asignar' => fn (int $id, V $s) => $q->unassignedCount($id, $s, $pending),
    'creados por hora (90 días)' => fn (int $id, V $s) => $q->countByHour($id, $s, 'created_at', $now->modify('-90 days')),
    'resueltos por hora (90 días)' => fn (int $id, V $s) => $q->countByHour($id, $s, 'resolved_at', $now->modify('-90 days')),
    'próximos a vencer' => fn (int $id, V $s) => $q->dueBetween($id, $s, $pending, $now, $now->modify('+48 hours'), 5),
    'asignados a mí' => fn (int $id, V $s) => $q->assignedTo($id, $s, $pending, 5),
    'carga por agente' => fn (int $id, V $s) => $q->workload($id, $s, $pending, $now, 8),
];
$sources['por estado y prioridad']($agent, $scopes['todo'][1]); // calienta la caché de MySQL antes de medir
foreach ($scopes as $scopeName => [$actor, $scope]) {
    echo "\n## {$scopeName}\n";
    foreach ($sources as $name => $source) {
        // El mejor de tres intentos: en Docker sobre Windows una sola medición tiene mucho ruido
        $ms = INF;
        for ($attempt = 0; $attempt < 3; $attempt++) {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $t = microtime(true);
            $source($actor, $scope);
            $ms = min($ms, round((microtime(true) - $t) * 1000, 1));
            $log = DB::getQueryLog();
            DB::disableQueryLog();
        }
        echo "### {$name}: {$ms} ms\n";
        foreach ($log as $entry) {
            if (! str_contains($entry['query'], 'incidents')) {
                continue;
            }
            foreach (DB::select('EXPLAIN '.$entry['query'], $entry['bindings']) as $row) {
                printf("  type=%-11s key=%-42s rows=%-7s %s\n", $row->type, $row->key ?? '-', $row->rows, $row->Extra ?? '');
            }
        }
    }
}
