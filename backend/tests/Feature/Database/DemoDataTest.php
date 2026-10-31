<?php

declare(strict_types=1);

// Los datos de demostración cumplen lo que pide la prueba técnica (500 o más incidentes, varios usuarios, todos los
// estados y prioridades) y cada incidente tiene un historial que explica cómo llegó a su estado.
// Documentado en docs/base-de-datos/seeders.md

use Database\Seeders\DatabaseSeeder;
use Database\Seeders\Support\IncidentStory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

const REQUIRED_INCIDENTS = 500;

/**
 * Historial sembrado por incidente, en orden.
 *
 * @return Collection<int, Collection<int, object>>
 */
function demoHistories(): Collection
{
    return DB::table('incident_activities')
        ->join('activity_types', 'activity_types.id', '=', 'incident_activities.activity_type_id')
        ->orderBy('incident_activities.created_at')->orderBy('incident_activities.id')
        ->get(['incident_id', 'user_id', 'activity_types.code as type', 'changes', 'incident_activities.created_at'])
        ->groupBy('incident_id');
}

/** @return list<string> Transiciones permitidas como "desde>hacia" */
function allowedTransitions(): array
{
    return DB::table('status_transitions')
        ->join('statuses as source', 'source.id', '=', 'status_transitions.from_status_id')
        ->join('statuses as target', 'target.id', '=', 'status_transitions.to_status_id')
        ->get(['source.code as from', 'target.code as to'])
        ->map(fn (object $row): string => "{$row->from}>{$row->to}")
        ->all();
}

// Las pruebas fijan SEED_INCIDENTS en 0, así que se lee el valor por defecto escrito en la configuración
it('trae por defecto al menos 500 incidentes', function (): void {
    preg_match('/env\(\'SEED_INCIDENTS\', (\d+)\)/', (string) file_get_contents(config_path('trazo.php')), $match);

    expect((int) ($match[1] ?? 0))->toBeGreaterThanOrEqual(REQUIRED_INCIDENTS);
});

it('siembra 500 incidentes con varios usuarios y todos los estados y prioridades', function (): void {
    config(['trazo.seed_incidents' => REQUIRED_INCIDENTS]);

    $this->seed(DatabaseSeeder::class);

    $incidents = DB::table('incidents');
    expect((clone $incidents)->count())->toBe(REQUIRED_INCIDENTS)
        ->and((clone $incidents)->distinct()->count('status_id'))->toBe(DB::table('statuses')->count())
        ->and((clone $incidents)->distinct()->count('priority_id'))->toBe(DB::table('priorities')->count())
        ->and((clone $incidents)->distinct()->count('created_by'))->toBeGreaterThan(3)
        ->and((clone $incidents)->whereNotNull('assigned_to')->distinct()->count('assigned_to'))->toBeGreaterThan(1)
        ->and(DB::table('users')->count())->toBeGreaterThan(1);
});

it('los recorridos del seeder solo usan transiciones permitidas', function (): void {
    $this->seed(DatabaseSeeder::class);
    $allowed = allowedTransitions();

    foreach (IncidentStory::PATHS as $status => $paths) {
        foreach ($paths as $path) {
            $from = 'open';
            foreach ($path as $to) {
                expect(in_array("{$from}>{$to}", $allowed, true))->toBeTrue("Recorrido hacia {$status}: {$from} > {$to}");
                $from = $to;
            }
            expect($from)->toBe($status);
        }
    }
});

it('cada incidente tiene un historial coherente con su estado, su asignación y sus fechas', function (): void {
    config(['trazo.seed_incidents' => 300]);
    $this->seed(DatabaseSeeder::class);

    $allowed = allowedTransitions();
    $now = Carbon::now()->addMinute();
    $people = DB::table('users')->pluck('ulid', 'id');
    $incidents = DB::table('incidents')->join('statuses', 'statuses.id', '=', 'incidents.status_id')
        ->get(['incidents.id', 'incidents.created_by', 'incidents.assigned_to', 'incidents.created_at', 'incidents.updated_at', 'incidents.resolved_at', 'statuses.code as status'])
        ->keyBy('id');

    foreach (demoHistories() as $id => $history) {
        $incident = $incidents[$id];
        $first = $history->first();
        $last = $history->last();

        // Empieza con la creación de quien lo creó y termina en la última vez que alguien lo tocó
        expect($first->type)->toBe('created')
            ->and($first->user_id)->toBe($incident->created_by)
            ->and($first->created_at)->toBe($incident->created_at)
            ->and($last->created_at)->toBe($incident->updated_at)
            ->and(Carbon::parse($last->created_at)->lessThanOrEqualTo($now))->toBeTrue();

        $status = 'open';
        $resolvedAt = null;
        foreach ($history->where('type', 'status_changed') as $change) {
            $data = json_decode((string) $change->changes, true);
            expect($data['status']['from']['code'])->toBe($status)
                ->and(in_array("{$status}>{$data['status']['to']['code']}", $allowed, true))->toBeTrue();
            $status = $data['status']['to']['code'];
            $resolvedAt = $status === 'resolved' ? $change->created_at : $resolvedAt;
        }
        expect($status)->toBe($incident->status);

        // "Resuelto" en el historial cae justo en resolved_at
        if ($incident->resolved_at !== null) {
            expect($resolvedAt)->toBe($incident->resolved_at);
        }

        $assignments = $history->where('type', 'assigned');
        if ($incident->assigned_to === null) {
            expect($assignments)->toBeEmpty();
        } else {
            $assignment = json_decode((string) $assignments->sole()->changes, true);
            expect($assignment['assignee']['to']['id'])->toBe($people[$incident->assigned_to]);
        }
    }

    expect(demoHistories())->toHaveCount($incidents->count());
});
