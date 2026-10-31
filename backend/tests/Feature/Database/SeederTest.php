<?php

declare(strict_types=1);

// Los seeders son idempotentes y respetan la matriz de permisos aprobada.
// Documentado en docs/base-de-datos/seeders.md

use App\Modules\Users\Infrastructure\Models\Role;
use App\Modules\Users\Infrastructure\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function tableCounts(): array
{
    $tables = ['priorities', 'statuses', 'status_transitions', 'activity_types', 'roles', 'permissions', 'role_permission', 'users', 'user_role'];

    return array_combine($tables, array_map(fn (string $t): int => DB::table($t)->count(), $tables));
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
            'activity_types' => 7, 'roles' => 4, 'permissions' => 23,
        ]);
});

it('no pisa lo que un administrador haya ajustado', function (): void {
    $this->seed(DatabaseSeeder::class);
    DB::table('priorities')->where('code', 'critical')->update(['default_resolution_minutes' => 120]);

    $this->seed(DatabaseSeeder::class);

    expect(DB::table('priorities')->where('code', 'critical')->value('default_resolution_minutes'))->toBe(120);
});

it('aplica la matriz de permisos por rol', function (): void {
    $this->seed(DatabaseSeeder::class);

    // El super admin tiene todo salvo el dashboard propio, que ya cubre dashboard.view.any
    expect(permissionsOf('super_admin'))->toHaveCount(22)->not->toContain('dashboard.view.own')
        ->and(permissionsOf('requester'))->toBe([
            'dashboard.view.own', 'incidents.create', 'incidents.update.own', 'incidents.view.own',
        ])
        ->and(permissionsOf('agent'))->toContain('incidents.take', 'incidents.status.assigned')
        ->not->toContain('incidents.assign', 'incidents.view.any')
        ->and(permissionsOf('supervisor'))->toContain('incidents.assign', 'audit.view')
        ->not->toContain('system.logs.view', 'users.manage');
});

it('crea usuarios de demostración con su rol y contraseña segura', function (): void {
    $this->seed(DatabaseSeeder::class);

    $admin = User::query()->where('email', 'superadmin@trazo.test')->firstOrFail();

    expect(User::query()->count())->toBe(8)
        ->and($admin->roles()->pluck('code')->all())->toBe(['super_admin'])
        ->and($admin->getAuthPassword())->toStartWith('$argon2id$');
});

it('nunca crea usuarios de demostración en producción', function (): void {
    app()->detectEnvironment(fn (): string => 'production');

    $this->seed(DemoUserSeeder::class);

    expect(User::query()->count())->toBe(0);
});
