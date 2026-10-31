<?php

declare(strict_types=1);

// Listado: paginación, orden, filtros, eliminados, visibilidad igual a la del detalle y sin N+1.
// Documentado en docs/backend/incidents.md

use App\Modules\Incidents\Domain\Actor;
use App\Modules\Incidents\Domain\IncidentAccess;
use App\Modules\Incidents\Domain\IncidentAction;
use App\Modules\Incidents\Domain\IncidentState;
use App\Modules\Incidents\Infrastructure\Models\Incident;
use App\Modules\Incidents\Infrastructure\Models\Priority;
use App\Modules\Incidents\Infrastructure\Models\Status;
use App\Modules\Users\Infrastructure\Models\User;
use App\Modules\Users\Infrastructure\UserPermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function demoUser(string $email): User
{
    return User::query()->where('email', $email)->firstOrFail();
}

// Un incidente con estado y prioridad por código
function incidentWith(array $attributes = [], string $status = 'open', string $priority = 'medium'): Incident
{
    return Incident::factory()->create([
        'status_id' => Status::query()->where('code', $status)->value('id'),
        'priority_id' => Priority::query()->where('code', $priority)->value('id'),
        ...$attributes,
    ]);
}

function listCodes(string $query = ''): array
{
    return array_column(test()->getJson("/api/v1/incidents?{$query}")->assertOk()->json('data'), 'code');
}

it('pagina con un máximo de 100 por página e informa el total', function (): void {
    Incident::factory()->count(25)->create();
    actingAsDemo('supervisor@trazo.test');

    $this->getJson('/api/v1/incidents?per_page=10&page=3')
        ->assertOk()
        ->assertJsonCount(5, 'data')
        ->assertJsonPath('meta', ['page' => 3, 'per_page' => 10, 'total' => 25, 'last_page' => 3])
        ->assertJsonMissingPath('data.0.description');

    $this->getJson('/api/v1/incidents?per_page=101')->assertUnprocessable()->assertJsonPath('errors.per_page.0.code', 'OUT_OF_RANGE');
});

it('ordena por los campos permitidos y rechaza cualquier otro', function (): void {
    incidentWith(['code' => 'INC-900001'], priority: 'low');
    incidentWith(['code' => 'INC-900002'], priority: 'critical');
    incidentWith(['code' => 'INC-900003'], priority: 'medium');
    actingAsDemo('supervisor@trazo.test');

    expect(listCodes('sort=-priority'))->toBe(['INC-900002', 'INC-900003', 'INC-900001']);
    $this->getJson('/api/v1/incidents?sort=password')->assertUnprocessable()->assertJsonPath('errors.sort.0.code', 'INVALID_OPTION');
});

it('filtra por estado, prioridad, asignado, creador y vencidos', function (): void {
    $agent = demoUser('agente1@trazo.test');
    $creator = demoUser('solicitante2@trazo.test');
    // Vencimiento futuro explícito: la factory lo elige al azar y podría quedar vencido
    incidentWith(['code' => 'INC-800001', 'assigned_to' => $agent->id, 'due_date' => now()->addDay()], 'in_progress', 'high');
    incidentWith(['code' => 'INC-800002', 'created_by' => $creator->id, 'due_date' => now()->addDay()], 'open', 'low');
    incidentWith(['code' => 'INC-800003', 'due_date' => now()->subDay()], 'on_hold', 'high');
    incidentWith(['code' => 'INC-800004', 'due_date' => now()->subDay()], 'closed', 'high');
    actingAsDemo('supervisor@trazo.test');

    expect(listCodes('filter[status]=in_progress,on_hold&sort=code'))->toBe(['INC-800001', 'INC-800003'])
        ->and(listCodes('filter[priority]=low'))->toBe(['INC-800002'])
        ->and(listCodes("filter[assignee]={$agent->ulid}"))->toBe(['INC-800001'])
        ->and(listCodes("filter[creator]={$creator->ulid}"))->toBe(['INC-800002'])
        // Un cerrado con la fecha pasada no está vencido
        ->and(listCodes('filter[overdue]=1'))->toBe(['INC-800003'])
        ->and(listCodes('filter[assignee]=none&filter[status]=open'))->toBe(['INC-800002']);
});

it('filtra por rango de creación y de vencimiento', function (): void {
    incidentWith(['code' => 'INC-700001', 'created_at' => now()->subDays(10), 'due_date' => now()->addDays(10)]);
    incidentWith(['code' => 'INC-700002', 'created_at' => now()->subDays(2), 'due_date' => now()->addDay()]);
    actingAsDemo('supervisor@trazo.test');
    $from = now()->subDays(3)->toIso8601String();
    $to = now()->addDays(2)->toIso8601String();

    expect(listCodes('filter[created_from]='.urlencode($from)))->toBe(['INC-700002'])
        ->and(listCodes('filter[due_to]='.urlencode($to)))->toBe(['INC-700002']);
});

it('muestra eliminados solo a quien puede restaurarlos', function (): void {
    incidentWith(['code' => 'INC-600001']);
    incidentWith(['code' => 'INC-600002'])->delete();

    actingAsDemo('solicitante1@trazo.test');
    $this->getJson('/api/v1/incidents?filter[deleted]=1')->assertForbidden();

    actingAsDemo('supervisor@trazo.test');
    expect(listCodes('filter[deleted]=1'))->toBe(['INC-600002'])
        ->and(listCodes())->toBe(['INC-600001']);
});

it('cada persona ve en el listado exactamente lo que el detalle le deja ver', function (): void {
    $people = User::query()->whereIn('email', ['solicitante1@trazo.test', 'agente1@trazo.test', 'agente2@trazo.test'])->get();
    foreach (range(1, 30) as $index) {
        Incident::factory()->create([
            'created_by' => $people->random()->id,
            'assigned_to' => $index % 3 === 0 ? null : $people->random()->id,
        ]);
    }
    $access = new IncidentAccess;

    foreach (User::query()->where('email', 'like', '%@trazo.test')->get() as $user) {
        $actor = new Actor($user->id, app(UserPermissions::class)->of($user->id));
        $expected = Incident::query()->with('status')->get()
            ->filter(fn (Incident $incident): bool => $access->denial(IncidentAction::View, $actor, new IncidentState(
                $incident->created_by, $incident->assigned_to, (bool) $incident->status?->is_initial, (bool) $incident->status?->is_final, false,
            )) === null)
            ->pluck('code')->sort()->values()->all();

        actingAsDemo($user->email);
        $listed = collect($this->getJson('/api/v1/incidents?per_page=100')->json('data'))->pluck('code')->sort()->values()->all();

        expect($listed)->toBe($expected, "Diferencia para {$user->email}");
    }
});

it('hace las mismas consultas con 5 o con 40 incidentes (sin N+1)', function (): void {
    actingAsDemo('supervisor@trazo.test');
    $count = function (): int {
        $this->getJson('/api/v1/incidents?per_page=100');
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->getJson('/api/v1/incidents?per_page=100')->assertOk();

        return count(DB::getQueryLog());
    };

    Incident::factory()->count(5)->assignedTo(demoUser('agente2@trazo.test'))->create();
    $few = $count();
    Incident::factory()->count(35)->assignedTo(demoUser('agente1@trazo.test'))->create();

    expect($count())->toBe($few);
});
