<?php

declare(strict_types=1);

// La base de datos protege por sí misma la trazabilidad: FKs, restricciones y datos limpios.
// Documentado en docs/base-de-datos/modelo.md

use App\Modules\Incidents\Infrastructure\Models\ActivityType;
use App\Modules\Incidents\Infrastructure\Models\Incident;
use App\Modules\Incidents\Infrastructure\Models\IncidentActivity;
use App\Modules\Incidents\Infrastructure\Models\Status;
use App\Modules\Users\Infrastructure\Models\Role;
use App\Modules\Users\Infrastructure\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('no permite borrar un usuario que creó un incidente', function (): void {
    $incident = Incident::factory()->create();

    expect(fn () => DB::table('users')->where('id', $incident->created_by)->delete())
        ->toThrow(QueryException::class);
});

it('no permite borrar un usuario que tiene incidentes asignados', function (): void {
    $agent = User::factory()->create();
    Incident::factory()->assignedTo($agent)->create();

    expect(fn () => DB::table('users')->where('id', $agent->id)->delete())
        ->toThrow(QueryException::class);
});

it('no permite borrar un estado que está en uso', function (): void {
    $incident = Incident::factory()->create();

    expect(fn () => DB::table('statuses')->where('id', $incident->status_id)->delete())
        ->toThrow(QueryException::class);
});

it('no permite borrar un rol que tienen usuarios', function (): void {
    expect(fn () => Role::query()->where('code', 'super_admin')->delete())
        ->toThrow(QueryException::class);
});

it('borrar un usuario sin historial elimina solo sus asignaciones de rol', function (): void {
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('code', 'agent')->value('id'));

    $user->delete();

    expect(DB::table('user_role')->where('user_id', $user->id)->count())->toBe(0)
        ->and(Role::query()->where('code', 'agent')->exists())->toBeTrue();
});

it('rechaza una transición de un estado hacia sí mismo', function (): void {
    $open = Status::query()->where('code', 'open')->value('id');

    expect(fn () => DB::table('status_transitions')->insert(['from_status_id' => $open, 'to_status_id' => $open]))
        ->toThrow(QueryException::class);
});

it('rechaza una referencia a un usuario que no existe', function (): void {
    expect(fn () => Incident::factory()->create(['assigned_to' => 999_999]))
        ->toThrow(QueryException::class);
});

it('mantiene el historial inmutable', function (): void {
    $incident = Incident::factory()->create();
    $activity = IncidentActivity::query()->create([
        'incident_id' => $incident->id,
        'user_id' => $incident->created_by,
        'activity_type_id' => ActivityType::query()->where('code', 'created')->value('id'),
    ]);

    expect(fn () => $activity->update(['changes' => ['x' => 1]]))->toThrow(LogicException::class)
        ->and(fn () => $activity->delete())->toThrow(LogicException::class);
});

it('borra los incidentes de forma lógica, sin perder su historial', function (): void {
    $incident = Incident::factory()->create();

    $incident->delete();

    expect(Incident::query()->find($incident->id))->toBeNull()
        ->and(Incident::withTrashed()->find($incident->id))->not->toBeNull();
});

it('normaliza el correo y genera un ULID público', function (): void {
    $user = User::factory()->create(['email' => '  Ana.Perez@Ejemplo.COM ']);

    expect($user->email)->toBe('ana.perez@ejemplo.com')
        ->and($user->ulid)->toMatch('/^[0-9A-HJKMNP-TV-Z]{26}$/i')
        ->and($user->getRouteKey())->toBe($user->ulid);
});

it('no permite dos usuarios con el mismo correo aunque cambien mayúsculas', function (): void {
    User::factory()->create(['email' => 'repetido@trazo.test']);

    expect(fn () => User::factory()->create(['email' => 'REPETIDO@trazo.test']))
        ->toThrow(QueryException::class);
});

it('tiene los índices pensados para filtros, orden, búsqueda y dashboard', function (): void {
    $indexes = collect(DB::select('SHOW INDEX FROM incidents'))
        ->groupBy('Key_name')
        ->map(fn ($cols) => $cols->sortBy('Seq_in_index')->pluck('Column_name')->implode(','));

    expect($indexes->values()->all())->toContain(
        'status_id,priority_id,deleted_at,created_by,assigned_to',
        'status_id,due_date,deleted_at',
        'assigned_to,status_id,due_date,deleted_at,created_by',
        'priority_id',
        'created_by',
        'due_date,deleted_at',
        'created_at,deleted_at',
        'resolved_at,deleted_at',
        'title,description',
    );
});

it('protege la grilla del dashboard con restricciones en la base', function (): void {
    $user = User::factory()->create();
    $widget = (int) DB::table('dashboard_widgets')->value('id');
    $row = ['user_id' => $user->id, 'dashboard_widget_id' => $widget, 'x' => 8, 'y' => 0, 'w' => 6, 'h' => 2, 'is_visible' => true];

    // Se sale de las 12 columnas
    expect(fn () => DB::table('user_dashboard_widgets')->insert($row))->toThrow(QueryException::class)
        // Un widget con topes incoherentes no entra al catálogo
        ->and(fn () => DB::table('dashboard_widgets')->where('id', $widget)->update(['min_w' => 7, 'max_w' => 6]))->toThrow(QueryException::class);
});
