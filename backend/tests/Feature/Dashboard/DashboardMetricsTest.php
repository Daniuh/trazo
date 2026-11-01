<?php

declare(strict_types=1);

// Métricas del dashboard: cada cifra coincide con el listado filtrado, la caché se invalida al cambiar un incidente,
// los días se cuentan en la hora del negocio y no hay N+1. Documentado en docs/backend/dashboard.md

use App\Modules\Dashboard\Application\Ports\MetricsCache;
use App\Modules\Incidents\Infrastructure\Models\Incident;
use App\Modules\Incidents\Infrastructure\Models\Priority;
use App\Modules\Incidents\Infrastructure\Models\Status;
use App\Modules\Users\Infrastructure\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

const ALL_WIDGETS = 'total_incidents,pending_incidents,overdue_incidents,unassigned_incidents,incidents_by_status,'
    .'incidents_by_priority,created_vs_resolved,due_soon,assigned_to_me,workload_by_agent';

function metricsIncident(string $status, string $priority = 'medium', array $attributes = []): Incident
{
    return Incident::factory()->create([
        'status_id' => Status::query()->where('code', $status)->value('id'),
        'priority_id' => Priority::query()->where('code', $priority)->value('id'),
        ...$attributes,
    ]);
}

/**
 * @return array<string, mixed>
 */
function metrics(string $widgets = ALL_WIDGETS, string $extra = ''): array
{
    return test()->getJson("/api/v1/dashboard/metrics?widgets={$widgets}{$extra}")->assertOk()->json('data');
}

// Total del listado con el filtro que el dashboard propone para esa cifra
function listedTotal(array $filter): int
{
    $query = http_build_query(['filter' => array_map(
        fn (mixed $value): mixed => is_array($value) ? implode(',', $value) : (is_bool($value) ? (int) $value : $value),
        $filter,
    ), 'per_page' => 1]);

    return (int) test()->getJson("/api/v1/incidents?{$query}")->assertOk()->json('meta.total');
}

beforeEach(function (): void {
    $agent = User::query()->where('email', 'agente1@trazo.test')->firstOrFail();
    $requester = User::query()->where('email', 'solicitante1@trazo.test')->firstOrFail();

    metricsIncident('open', 'critical', ['due_date' => now()->addHours(5)]);
    metricsIncident('open', 'low', ['assigned_to' => $agent->id, 'due_date' => now()->subDay()]);
    metricsIncident('in_progress', 'high', ['assigned_to' => $agent->id, 'due_date' => now()->addHours(30)]);
    metricsIncident('on_hold', 'medium', ['created_by' => $requester->id, 'due_date' => now()->subHours(2)]);
    metricsIncident('closed', 'high', ['created_by' => $requester->id, 'due_date' => now()->subDays(3), 'resolved_at' => now()->subDay()]);
    metricsIncident('resolved', 'medium', ['assigned_to' => $agent->id, 'resolved_at' => now()->subHours(3)]);
    metricsIncident('open', 'low', ['deleted_at' => now()]);
});

it('cada cifra coincide con lo que el listado muestra con su filtro, para cada rol', function (string $email): void {
    actingAsDemo($email);
    // Se piden justo los widgets que el dashboard le ofrece a esa persona
    $data = metrics(implode(',', array_column($this->getJson('/api/v1/dashboard')->json('data.widgets'), 'code')));

    foreach (['total_incidents', 'pending_incidents', 'overdue_incidents', 'unassigned_incidents'] as $kpi) {
        if (isset($data[$kpi])) {
            expect($data[$kpi]['value'])->toBe(listedTotal($data[$kpi]['filter']), "{$kpi} de {$email}");
        }
    }
    foreach ([...$data['incidents_by_status']['items'], ...$data['incidents_by_priority']['items']] as $item) {
        expect($item['count'])->toBe(listedTotal($item['filter']), "{$item['code']} de {$email}");
    }
    expect($data['due_soon']['total'])->toBe(listedTotal($data['due_soon']['filter']));
    foreach ($data['workload_by_agent']['items'] ?? [] as $row) {
        expect($row['pending'])->toBe(listedTotal($row['filter']));
    }
})->with(['supervisor@trazo.test', 'agente1@trazo.test', 'solicitante1@trazo.test']);

it('calcula vencidos, porcentajes y listas cortas sin contar los eliminados', function (): void {
    actingAsDemo('supervisor@trazo.test');
    $data = metrics();

    expect($data['total_incidents']['value'])->toBe(6)
        ->and($data['pending_incidents']['value'])->toBe(4)
        ->and($data['overdue_incidents'])->toMatchArray(['value' => 2, 'share_of_pending' => 50])
        ->and(array_sum(array_column($data['incidents_by_priority']['items'], 'percent')))->toBe(100)
        ->and(array_column($data['due_soon']['items'], 'title'))->toHaveCount(2)
        ->and($data['workload_by_agent']['items'][0])->toMatchArray(['name' => 'Laura Gómez', 'pending' => 2, 'overdue' => 1]);
});

it('la lista "Asignados a mí" es de quien pregunta y marca los vencidos', function (): void {
    actingAsDemo('agente1@trazo.test');
    $mine = metrics('assigned_to_me')['assigned_to_me'];

    expect($mine['total'])->toBe(2)
        ->and($mine['items'][0]['is_overdue'])->toBeTrue()
        ->and($mine['items'][1]['is_overdue'])->toBeFalse();
});

it('no entrega widgets que la persona no puede usar', function (): void {
    actingAsDemo('solicitante1@trazo.test');

    $this->getJson('/api/v1/dashboard/metrics?widgets=workload_by_agent')
        ->assertUnprocessable()->assertJsonPath('code', 'WIDGET_NOT_AVAILABLE');
    $this->getJson('/api/v1/dashboard/metrics?widgets=total_incidents&trend_days=15')
        ->assertUnprocessable();
});

it('un cambio en un incidente invalida la caché al momento', function (): void {
    actingAsDemo('supervisor@trazo.test');
    expect(metrics('total_incidents')['total_incidents']['value'])->toBe(6);

    $this->postJson('/api/v1/incidents', ['title' => 'Nuevo para el dashboard', 'description' => 'Prueba de caché.', 'priority' => 'low'], ['Idempotency-Key' => (string) Str::uuid()])
        ->assertCreated();

    expect(metrics('total_incidents')['total_incidents']['value'])->toBe(7);
});

it('cuenta los días de la tendencia en la hora del negocio, no en UTC', function (): void {
    config(['trazo.dashboard.timezone' => 'America/Bogota']);
    $this->travelTo(new DateTimeImmutable('2026-10-22 15:00:00', new DateTimeZone('UTC')));
    // 03:00 UTC del 22 son las 22:00 del 21 en Bogotá
    metricsIncident('open', attributes: ['created_at' => new DateTimeImmutable('2026-10-22 03:00:00', new DateTimeZone('UTC'))]);
    actingAsDemo('supervisor@trazo.test');

    $series = collect(metrics('created_vs_resolved', '&trend_days=7')['created_vs_resolved']['series'])->keyBy('date');

    expect($series)->toHaveCount(7)
        ->and($series['2026-10-21']['created'])->toBeGreaterThanOrEqual(1)
        ->and($series->keys()->last())->toBe('2026-10-22');
});

it('hace las mismas consultas con pocos o muchos incidentes (sin N+1)', function (): void {
    actingAsDemo('supervisor@trazo.test');
    $count = function (): int {
        app(MetricsCache::class)->invalidate();
        DB::flushQueryLog();
        DB::enableQueryLog();
        metrics();

        return count(DB::getQueryLog());
    };

    // La primera petición también lee permisos y catálogos, que luego quedan en memoria
    metrics();
    $few = $count();
    $agent = User::query()->where('email', 'agente2@trazo.test')->firstOrFail();
    Incident::factory()->count(30)->assignedTo($agent)->create();

    expect($count())->toBe($few);
});

it('el pulso, el mapa de riesgo y el flujo cuadran con el listado filtrado', function (string $email): void {
    actingAsDemo($email);
    $data = metrics('operation_pulse,risk_map,status_flow');

    $pulse = $data['operation_pulse'];
    foreach (['total', 'pending', 'overdue', 'critical'] as $figure) {
        expect($pulse[$figure]['value'])->toBe(listedTotal($pulse[$figure]['filter']), "{$figure} de {$email}");
    }
    foreach ($data['risk_map']['rows'] as $row) {
        foreach ($row['cells'] as $cell) {
            expect($cell['count'])->toBe(listedTotal($cell['filter']), "{$row['code']}/{$cell['bucket']} de {$email}");
        }
        expect($row['total']['count'])->toBe(listedTotal($row['total']['filter']), "total {$row['code']} de {$email}");
    }
    foreach ([...$data['status_flow']['stages'], ...$data['status_flow']['exits']] as $stage) {
        expect($stage['count'])->toBe(listedTotal($stage['filter']), "{$stage['code']} de {$email}");
    }
})->with(['supervisor@trazo.test', 'agente1@trazo.test', 'solicitante1@trazo.test']);

it('arma el mapa de riesgo de la prioridad más alta a la más baja, y el flujo con Cancelado como salida', function (): void {
    actingAsDemo('supervisor@trazo.test');
    $data = metrics('risk_map,status_flow');

    expect($data['risk_map']['columns'])->toBe(['overdue', 'today', 'week', 'later'])
        ->and(array_column($data['risk_map']['rows'], 'code'))->toBe(['critical', 'high', 'medium', 'low'])
        // El crítico vence en 5 horas: hoy o mañana según la hora del negocio, nunca vencido
        ->and($data['risk_map']['rows'][0]['cells'][0]['count'])->toBe(0)
        ->and(array_column($data['status_flow']['stages'], 'code'))->toBe(['open', 'in_progress', 'on_hold', 'resolved', 'closed'])
        ->and(array_column($data['status_flow']['exits'], 'code'))->toBe(['cancelled']);
});

it('calcula el cumplimiento del plazo con lo resuelto en los últimos 30 días', function (): void {
    metricsIncident('closed', attributes: ['due_date' => now()->subDays(2), 'resolved_at' => now()->subDays(3)]);
    metricsIncident('resolved', attributes: ['due_date' => now()->subDays(5), 'resolved_at' => now()->subDays(4)]);
    // Fuera de la ventana: no cuenta
    metricsIncident('closed', attributes: ['due_date' => now()->subDays(50), 'resolved_at' => now()->subDays(40)]);
    actingAsDemo('supervisor@trazo.test');

    // Del beforeEach: un cerrado tarde (vence hace 3 días, resuelto ayer) y un resuelto hace 3 h con vencimiento al azar
    $onTime = metrics('operation_pulse')['operation_pulse']['on_time'];
    $resolvedRecently = Incident::query()->where('resolved_at', '>=', now()->subDays(30))->get();
    $expected = (int) round($resolvedRecently->filter(fn (Incident $i): bool => $i->resolved_at <= $i->due_date)->count() * 100 / $resolvedRecently->count());

    expect($onTime)->toMatchArray(['days' => 30, 'resolved' => $resolvedRecently->count(), 'percent' => $expected]);
});

it('reconstruye el backlog de cada día: hoy son los pendientes de ahora y hacia atrás suma lo que salió', function (): void {
    config(['trazo.dashboard.timezone' => 'America/Bogota']);
    $this->travelTo(new DateTimeImmutable('2026-10-22 15:00:00', new DateTimeZone('UTC')));
    $yesterday = new DateTimeImmutable('2026-10-21 15:00:00', new DateTimeZone('UTC'));
    // Ayer se resolvió uno y se canceló otro sin resolverse; hoy se creó uno
    metricsIncident('closed', attributes: ['created_at' => $yesterday->modify('-5 days'), 'resolved_at' => $yesterday]);
    metricsIncident('cancelled', attributes: ['created_at' => $yesterday->modify('-5 days'), 'updated_at' => $yesterday, 'resolved_at' => null]);
    metricsIncident('open', attributes: ['created_at' => new DateTimeImmutable('2026-10-22 14:00:00', new DateTimeZone('UTC'))]);
    actingAsDemo('supervisor@trazo.test');

    $balance = metrics('backlog_balance', '&trend_days=7')['backlog_balance'];
    $series = collect($balance['series'])->keyBy('date');
    $today = $series['2026-10-22'];

    expect($series)->toHaveCount(7)
        ->and($today['pending'])->toBe(listedTotal($balance['filter']))
        ->and($series['2026-10-21']['pending'])->toBe($today['pending'] - $today['created'] + $today['exited'])
        ->and($series['2026-10-21']['exited'])->toBeGreaterThanOrEqual(2)
        ->and($balance['change'])->toBe($today['pending'] - $series->first()['pending']);
});
