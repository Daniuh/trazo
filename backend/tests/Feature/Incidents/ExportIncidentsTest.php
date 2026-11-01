<?php

declare(strict_types=1);

// Exportación en CSV: formato para Excel, misma visibilidad y orden del listado, protección contra fórmulas, tope,
// permisos, límite de tasa y registro en la auditoría. Documentado en docs/backend/incidents.md

use App\Modules\Incidents\Infrastructure\Models\Incident;
use App\Modules\Incidents\Infrastructure\Models\Priority;
use App\Modules\Incidents\Infrastructure\Models\Status;
use App\Modules\Users\Infrastructure\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

function exportUser(string $email): User
{
    return User::query()->where('email', $email)->firstOrFail();
}

function exportIncident(array $attributes = [], string $status = 'open', string $priority = 'medium'): Incident
{
    return Incident::factory()->create([
        'status_id' => Status::query()->where('code', $status)->value('id'),
        'priority_id' => Priority::query()->where('code', $priority)->value('id'),
        ...$attributes,
    ]);
}

function grantExport(string $email, string $permission): void
{
    DB::table('user_permissions')->insert([
        'user_id' => exportUser($email)->id,
        'permission_id' => DB::table('permissions')->where('code', $permission)->value('id'),
        'effect' => 'grant',
        'granted_by' => exportUser('superadmin@trazo.test')->id,
    ]);
}

/**
 * Filas del archivo, sin el BOM, ya separadas en celdas.
 *
 * @return list<list<string>>
 */
function csvRows(TestResponse $response): array
{
    $content = (string) $response->streamedContent();
    expect(str_starts_with($content, "\xEF\xBB\xBF"))->toBeTrue();
    $lines = array_filter(explode("\r\n", substr($content, 3)), fn (string $line): bool => $line !== '');

    return array_values(array_map(fn (string $line): array => str_getcsv($line, ';', '"', ''), $lines));
}

function exportedCodes(string $query = ''): array
{
    return array_column(array_slice(csvRows(test()->get("/api/v1/incidents/export?{$query}")->assertOk()), 1), 0);
}

it('entrega un CSV para Excel en español, con las fechas en la hora del negocio', function (): void {
    $this->travelTo('2026-03-01 12:00:00');
    $agent = exportUser('agente1@trazo.test');
    exportIncident([
        'code' => 'TZ-90-0001', 'title' => 'Impresora sin tóner; piso 3', 'assigned_to' => $agent->id,
        'created_at' => '2026-02-10 15:00:00', 'due_date' => '2026-02-20 15:30:00',
    ], 'in_progress', 'high');
    exportIncident(['code' => 'TZ-90-0002', 'title' => 'Correo caído', 'due_date' => '2026-03-05 10:00:00'], 'open', 'low');
    actingAsDemo('supervisor@trazo.test');

    $response = $this->get('/api/v1/incidents/export?sort=code')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
        ->assertDownload('incidentes-2026-03-01-0700.csv');
    $rows = csvRows($response);

    expect($rows[0])->toBe([
        'Código', 'Título', 'Estado', 'Prioridad', 'Creado por', 'Asignado a',
        'Creado (UTC-05:00)', 'Vencimiento (UTC-05:00)', 'Resuelto (UTC-05:00)', 'Vencido',
    ])
        ->and($rows[1])->toBe([
            'TZ-90-0001', 'Impresora sin tóner; piso 3', 'En progreso', 'Alta', $rows[1][4], 'Laura Gómez',
            '2026-02-10 10:00', '2026-02-20 10:30', '', 'Sí',
        ])
        ->and($rows[2][5])->toBe('Sin asignar')
        ->and($rows[2][9])->toBe('No');
});

it('exporta lo mismo que ve en el listado, en el mismo orden', function (string $email): void {
    $agent = exportUser('agente1@trazo.test');
    $requester = exportUser('solicitante1@trazo.test');
    exportIncident(['assigned_to' => $agent->id]);
    exportIncident(['created_by' => $requester->id]);
    exportIncident(['assigned_to' => null]);
    exportIncident(['assigned_to' => exportUser('agente2@trazo.test')->id], 'in_progress');
    Incident::factory()->count(6)->create();
    if ($email !== 'supervisor@trazo.test') {
        grantExport($email, 'incidents.export');
    }
    actingAsDemo($email);

    foreach (['sort=code', 'sort=-priority', 'filter[status]=open&sort=-due_date'] as $query) {
        $listed = array_column($this->getJson("/api/v1/incidents?per_page=100&{$query}")->assertOk()->json('data'), 'code');
        expect(exportedCodes($query))->toBe($listed);
    }
})->with(['supervisor@trazo.test', 'agente1@trazo.test', 'solicitante1@trazo.test']);

it('lee por bloques sin repetir ni saltar filas', function (): void {
    config(['trazo.export.chunk_size' => 2]);
    Incident::factory()->count(7)->create();
    actingAsDemo('supervisor@trazo.test');

    $codes = exportedCodes('sort=-created_at');

    expect($codes)->toHaveCount(7)->and(array_unique($codes))->toHaveCount(7);
});

it('guarda como texto lo que Excel leería como fórmula', function (string $title): void {
    exportIncident(['title' => $title]);
    actingAsDemo('supervisor@trazo.test');

    expect(csvRows($this->get('/api/v1/incidents/export'))[1][1])->toBe("'{$title}");
})->with(['=HYPERLINK("http://malo.test")', '+1+1', '-2+3', '@SUM(A1)']);

it('no incluye la descripción', function (): void {
    exportIncident(['description' => 'Texto largo que no debe salir']);
    actingAsDemo('supervisor@trazo.test');

    $content = (string) $this->get('/api/v1/incidents/export')->streamedContent();

    expect($content)->not->toContain('Texto largo que no debe salir')->not->toContain('Descripción');
});

it('rechaza lo que pasa del tope con el total, sin registrar nada', function (): void {
    config(['trazo.export.max_rows' => 2]);
    Incident::factory()->count(3)->create();
    actingAsDemo('supervisor@trazo.test');

    $this->getJson('/api/v1/incidents/export')
        ->assertUnprocessable()
        ->assertJsonPath('code', 'EXPORT_TOO_LARGE')
        ->assertJsonPath('params', ['total' => 3, 'max' => 2]);
    expect(DB::table('admin_actions')->count())->toBe(0);
});

it('exige el permiso de exportar', function (): void {
    actingAsDemo('agente1@trazo.test');

    $this->getJson('/api/v1/incidents/export')->assertForbidden();
});

it('exige además el de restaurar para exportar los eliminados', function (): void {
    grantExport('agente1@trazo.test', 'incidents.export');
    actingAsDemo('agente1@trazo.test');

    $this->getJson('/api/v1/incidents/export?filter[deleted]=1')->assertForbidden();
    $this->get('/api/v1/incidents/export')->assertOk();
});

it('valida los filtros igual que el listado', function (): void {
    actingAsDemo('supervisor@trazo.test');

    $this->getJson('/api/v1/incidents/export?sort=password')
        ->assertUnprocessable()
        ->assertJsonPath('errors.sort.0.code', 'INVALID_OPTION');
});

it('deja constancia de cada exportación con sus filtros y filas', function (): void {
    exportIncident(['title' => 'Uno'], 'open', 'high');
    exportIncident(['title' => 'Dos'], 'open', 'low');
    $supervisor = actingAsDemo('supervisor@trazo.test');

    $this->get('/api/v1/incidents/export?filter[status]=open&filter[priority]=high&search=impresora')->assertOk();
    $this->get('/api/v1/incidents/export?filter[priority]=high')->assertOk();

    $action = DB::table('admin_actions')->latest('id')->first();
    expect($action->user_id)->toBe($supervisor->id)
        ->and(DB::table('admin_action_types')->where('id', $action->admin_action_type_id)->value('code'))->toBe('incidents_exported')
        ->and($action->target_user_id)->toBeNull()
        ->and(json_decode($action->details, true))->toBe(['rows' => 1, 'criteria' => ['priority' => [['code' => 'high', 'name' => 'Alta']]]]);

    $first = json_decode((string) DB::table('admin_actions')->oldest('id')->value('details'), true);
    expect($first['criteria'])->toBe([
        'search' => 'impresora',
        'status' => [['code' => 'open', 'name' => 'Abierto']],
        'priority' => [['code' => 'high', 'name' => 'Alta']],
    ]);
});

it('permite 5 exportaciones por minuto por persona', function (): void {
    actingAsDemo('supervisor@trazo.test');

    foreach (range(1, 5) as $attempt) {
        $this->get('/api/v1/incidents/export')->assertOk();
    }

    $this->getJson('/api/v1/incidents/export')->assertTooManyRequests()->assertHeader('Retry-After');
});
