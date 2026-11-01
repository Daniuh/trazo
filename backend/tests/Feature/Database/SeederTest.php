<?php

declare(strict_types=1);

// Los seeders son idempotentes y respetan la matriz de permisos aprobada.
// Documentado en docs/base-de-datos/seeders.md

use App\Modules\Users\Infrastructure\Models\Role;
use App\Modules\Users\Infrastructure\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\IncidentVolumeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function tableCounts(): array
{
    $tables = ['priorities', 'statuses', 'status_transitions', 'activity_types', 'roles', 'permissions', 'role_permission', 'users', 'user_role'];

    return array_combine($tables, array_map(fn (string $t): int => DB::table($t)->count(), $tables));
}

// En producción db:seed pide confirmación: sin --force no corre nada, igual que en el arranque del contenedor
function seedInProduction(string $seeder): void
{
    app()->detectEnvironment(fn (): string => 'production');
    test()->artisan('db:seed', ['--class' => $seeder, '--force' => true])->assertSuccessful();
}

function permissionsOf(string $role): array
{
    return Role::query()->where('code', $role)->firstOrFail()->permissions()->pluck('code')->sort()->values()->all();
}

it('se puede ejecutar varias veces sin duplicar nada', function (): void {
    $this->seed(DatabaseSeeder::class);
    $first = tableCounts();

    $this->seed(DatabaseSeeder::class);

    expect(tableCounts())->toBe($first)
        ->and($first)->toMatchArray([
            'priorities' => 4, 'statuses' => 6, 'status_transitions' => 9,
            'activity_types' => 10, 'roles' => 5, 'permissions' => 33,
        ]);
});

it('no pisa lo que un administrador haya ajustado', function (): void {
    $this->seed(DatabaseSeeder::class);
    DB::table('priorities')->where('code', 'critical')->update(['default_resolution_minutes' => 120]);

    $this->seed(DatabaseSeeder::class);

    expect(DB::table('priorities')->where('code', 'critical')->value('default_resolution_minutes'))->toBe(120);
});

it('no devuelve a un rol un permiso que la administración le quitó', function (): void {
    $this->seed(DatabaseSeeder::class);
    $agent = Role::query()->where('code', 'agent')->firstOrFail();
    $agent->permissions()->detach(DB::table('permissions')->where('code', 'incidents.take')->value('id'));

    $this->seed(DatabaseSeeder::class);

    expect(permissionsOf('agent'))->not->toContain('incidents.take');
});

it('aplica la matriz de permisos por rol, con su nivel', function (): void {
    $this->seed(DatabaseSeeder::class);

    // El super admin tiene todo salvo el dashboard propio, que ya cubre dashboard.view.any
    expect(permissionsOf('super_admin'))->toHaveCount(32)->not->toContain('dashboard.view.own')
        ->and(permissionsOf('requester'))->toBe([
            'dashboard.view.own', 'incidents.comment', 'incidents.create', 'incidents.reopen.request', 'incidents.update.own', 'incidents.view.own',
        ])
        ->and(permissionsOf('agent'))->toContain('incidents.take', 'incidents.status.assigned')
        ->not->toContain('incidents.assign', 'incidents.view.any')
        ->and(permissionsOf('supervisor'))->toContain('incidents.assign', 'audit.incidents.view', 'incidents.reopen.approve')
        ->not->toContain('system.logs.view', 'users.view', 'audit.admin.view')
        // Restablecer accesos queda solo para el super admin, salvo que se le conceda a una persona
        ->and(permissionsOf('admin'))->toContain('users.create', 'users.roles.assign', 'users.permissions.grant', 'audit.admin.view')
        ->not->toContain('users.mfa.reset', 'users.access.reset', 'roles.manage', 'system.logs.view')
        ->and(Role::query()->orderBy('level')->pluck('level', 'code')->all())->toBe([
            'requester' => 20, 'agent' => 40, 'supervisor' => 60, 'admin' => 80, 'super_admin' => 100,
        ]);
});

it('crea usuarios de demostración con su rol y contraseña segura', function (): void {
    $this->seed(DatabaseSeeder::class);

    $admin = User::query()->where('email', 'superadmin@trazo.test')->firstOrFail();

    expect(User::query()->count())->toBe(9)
        ->and($admin->roles()->pluck('code')->all())->toBe(['super_admin'])
        ->and($admin->getAuthPassword())->toStartWith('$argon2id$');
});

it('nunca crea usuarios de demostración en producción', function (): void {
    User::query()->where('email', 'like', '%@trazo.test')->each(fn (User $user) => $user->roles()->detach());
    User::query()->where('email', 'like', '%@trazo.test')->delete();
    seedInProduction(DemoUserSeeder::class);

    expect(User::query()->count())->toBe(0);
});

it('volver a sembrar no gasta valores del autoincremental de los catálogos', function (): void {
    $next = fn (): int => (int) DB::table('activity_types')->insertGetId(['code' => 'probe_'.uniqid(), 'name' => 'Prueba']);
    $before = $next();

    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    // Si el seeder hubiera intentado insertar filas existentes, el siguiente id habría saltado
    expect($next())->toBe($before + 1);
});

it('en producción solo carga incidentes de demostración si se pide a propósito', function (): void {
    config(['trazo.seed_incidents' => 20]);

    seedInProduction(IncidentVolumeSeeder::class);
    expect(DB::table('incidents')->count())->toBe(0);

    config(['trazo.seed_incidents_in_production' => true]);
    seedInProduction(IncidentVolumeSeeder::class);
    expect(DB::table('incidents')->count())->toBe(20);
});

it('sin personas que atiendan los incidentes, los de demostración se omiten sin fallar', function (): void {
    config(['trazo.seed_incidents' => 20]);
    DB::table('users')->update(['is_active' => false]);

    $this->seed(IncidentVolumeSeeder::class);

    expect(DB::table('incidents')->count())->toBe(0);
});

it('carga el volumen de incidentes pedido, coherente y sin duplicar al repetir', function (): void {
    config(['trazo.seed_incidents' => 60]);

    $this->seed(IncidentVolumeSeeder::class);
    $this->seed(IncidentVolumeSeeder::class);

    $incidents = DB::table('incidents')->join('statuses', 'statuses.id', '=', 'incidents.status_id')
        ->get(['incidents.code', 'incidents.resolved_at', 'statuses.code as status']);

    expect($incidents)->toHaveCount(60)
        ->and($incidents->pluck('code')->every(fn (string $code): bool => preg_match('/^TZ-\d{2}-\d{4,}$/', $code) === 1))->toBeTrue()
        ->and($incidents->whereIn('status', ['resolved', 'closed'])->whereNull('resolved_at'))->toBeEmpty()
        ->and($incidents->whereIn('status', ['open', 'in_progress', 'on_hold'])->whereNotNull('resolved_at'))->toBeEmpty()
        ->and(DB::table('incident_activities')->distinct()->count('incident_id'))->toBe(60);
});
