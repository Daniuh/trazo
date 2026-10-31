<?php

declare(strict_types=1);

// Códigos de incidente por año: consecutivos al crear, de nuevo desde 1 al cambiar de año (en la zona horaria del
// negocio) y numeración en bloque de los que llegan con el formato anterior. Documentado en docs/backend/incidents.md

use App\Modules\Incidents\Infrastructure\IncidentCodes;
use App\Modules\Incidents\Infrastructure\Models\Incident;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function createdCode(): string
{
    return (string) test()->postJson('/api/v1/incidents', ['title' => 'Sin red en el piso 3', 'description' => 'x', 'priority' => 'low'])
        ->assertCreated()
        ->json('data.code');
}

it('cada incidente toma el siguiente número de su año, y el año nuevo empieza en 1', function (): void {
    actingAsDemo('solicitante1@trazo.test');

    // 31 de diciembre a las 11 p. m. en Bogotá: en UTC ya es 2027, pero el año del negocio sigue siendo 2026
    $this->travelTo(Carbon::parse('2027-01-01 04:00:00', 'UTC'));
    expect([createdCode(), createdCode()])->toBe(['TZ-26-0001', 'TZ-26-0002']);

    $this->travelTo(Carbon::parse('2027-01-01 06:00:00', 'UTC'));
    expect(createdCode())->toBe('TZ-27-0001')
        ->and(DB::table('incident_code_sequences')->orderBy('year')->pluck('last_number', 'year')->all())->toBe([2026 => 2, 2027 => 1]);
});

it('numera los del formato anterior por año y en orden de creación, después de los que ya tenían número', function (): void {
    DB::table('incident_code_sequences')->insert(['year' => 2026, 'last_number' => 5]);
    $old = fn (string $code, string $createdAt) => Incident::factory()->create(['code' => $code, 'created_at' => $createdAt]);
    $late = $old('INC-000001', '2026-03-01 12:00:00');
    $early = $old('INC-000002', '2026-01-10 12:00:00');
    $previousYear = $old('INC-000003', '2025-12-31 12:00:00');

    IncidentCodes::fromConfig()->assignPending('INC-');

    expect($early->fresh()?->code)->toBe('TZ-26-0006')
        ->and($late->fresh()?->code)->toBe('TZ-26-0007')
        ->and($previousYear->fresh()?->code)->toBe('TZ-25-0001')
        ->and(DB::table('incident_code_sequences')->orderBy('year')->pluck('last_number', 'year')->all())->toBe([2025 => 1, 2026 => 7]);
});
