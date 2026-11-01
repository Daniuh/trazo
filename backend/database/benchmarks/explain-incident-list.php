<?php

declare(strict_types=1);

// Mide el listado de incidentes: tiempo, cantidad de consultas y plan de MySQL (EXPLAIN) del conteo y de la página.
// Se ejecuta sobre la base de pruebas cargada con mucho volumen; los pasos están en docs/base-de-datos/rendimiento.md

use App\Modules\Incidents\Application\Data\IncidentListCriteria as C;
use App\Modules\Incidents\Application\Ports\IncidentListQuery;
use App\Modules\Incidents\Domain\IncidentGroup as G;
use App\Modules\Incidents\Domain\IncidentSort as S;
use App\Modules\Incidents\Domain\VisibilityScope as V;
use Illuminate\Support\Facades\DB;

$q = app(IncidentListQuery::class);
$agent = (int) DB::table('users')->where('email', 'agente1@trazo.test')->value('id');
$req = (int) DB::table('users')->where('email', 'solicitante1@trazo.test')->value('id');
$all = new V(true, false, false, false);
$agentScope = new V(false, true, true, true);
$reqScope = new V(false, true, false, false);
$cases = [
    'todo, orden por creación' => [$agent, $all, new C],
    'agente (propios, asignados, sin asignar)' => [$agent, $agentScope, new C],
    'solicitante (propios)' => [$req, $reqScope, new C],
    'estado + prioridad, orden por prioridad' => [$agent, $all, new C(sort: S::Priority, statuses: ['open', 'in_progress'], priorities: ['high', 'critical'])],
    'vencidos, orden por vencimiento' => [$agent, $all, new C(sort: S::DueDate, descending: false, overdue: true)],
    'asignado a una persona' => [$agent, $all, new C(assignees: [DB::table('users')->where('id', $agent)->value('ulid')])],
    'rango de vencimiento' => [$agent, $all, new C(sort: S::DueDate, dueFrom: new DateTimeImmutable('-3 days'), dueTo: new DateTimeImmutable('+3 days'))],
    'búsqueda de texto' => [$agent, $all, new C(search: 'impresora')],
    // Lista agrupada (ADR-0080): orden por grupo y conteo por grupo
    'agrupado por vencimiento' => [$agent, $all, new C(group: G::Due)],
    'agente, agrupado por vencimiento' => [$agent, $agentScope, new C(group: G::Due)],
    'agrupado por estado' => [$agent, $all, new C(group: G::Status)],
    'agrupado por prioridad' => [$agent, $all, new C(group: G::Priority)],
    'agrupado por asignado' => [$agent, $all, new C(group: G::Assignee)],
    'pendientes agrupados por vencimiento, orden por vencimiento' => [$agent, $all, new C(sort: S::DueDate, descending: false, statuses: ['open', 'in_progress', 'on_hold'], group: G::Due)],
];
$q->search($agent, $all, new C); // calienta la caché antes de medir
foreach ($cases as $name => [$actor, $scope, $criteria]) {
    // El mejor de tres intentos: en Docker sobre Windows una sola medición tiene mucho ruido
    $ms = INF;
    for ($attempt = 0; $attempt < 3; $attempt++) {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $t = microtime(true);
        $page = $q->search($actor, $scope, $criteria);
        $ms = min($ms, round((microtime(true) - $t) * 1000));
        $log = DB::getQueryLog();
        DB::disableQueryLog();
    }
    echo "\n### {$name} — total {$page->total}, {$ms} ms, ".count($log)." consultas\n";
    foreach ($log as $entry) {
        $kind = match (true) {
            str_contains($entry['query'], 'group_key') => 'grupos',
            str_starts_with($entry['query'], 'select `incidents`.`id`') => 'página del grupo',
            str_starts_with($entry['query'], 'select count(*)') => 'conteo',
            str_contains($entry['query'], 'limit 20') => 'página',
            default => null,
        };
        if ($kind === null) {
            continue;
        }
        echo "  [$kind]
";
        foreach (DB::select('EXPLAIN '.$entry['query'], $entry['bindings']) as $row) {
            printf("  %-10s type=%-11s key=%-38s rows=%-7s %s\n", $row->table, $row->type, $row->key ?? '-', $row->rows, $row->Extra ?? '');
        }
    }
}
