<?php

declare(strict_types=1);

// Sin N+1 en los listados de la administración: personas y roles hacen las mismas consultas con pocos o con muchos
// registros. Documentado en docs/base-de-datos/rendimiento.md

use App\Modules\Users\Infrastructure\Models\Role;
use App\Modules\Users\Infrastructure\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

// Consultas de una petición; la primera se descarta (calienta catálogos y permisos de la sesión)
function queriesOf(string $uri): int
{
    test()->getJson($uri)->assertOk();
    DB::flushQueryLog();
    DB::enableQueryLog();
    test()->getJson($uri)->assertOk();

    return count(DB::getQueryLog());
}

function peopleWithRole(int $count, string $role): void
{
    $roleId = (int) Role::query()->where('code', $role)->value('id');
    User::factory()->count($count)->create()->each(fn (User $user) => $user->roles()->attach($roleId));
}

it('el listado de personas hace las mismas consultas con 5 o con 40', function (): void {
    actingAsDemo('superadmin@trazo.test');

    peopleWithRole(5, 'agent');
    $few = queriesOf('/api/v1/users?per_page=100');
    peopleWithRole(35, 'requester');

    expect(queriesOf('/api/v1/users?per_page=100'))->toBe($few);
});

it('el listado de roles hace las mismas consultas con más roles y más personas', function (): void {
    actingAsDemo('superadmin@trazo.test');

    $few = queriesOf('/api/v1/roles');
    foreach (range(1, 6) as $index) {
        DB::table('roles')->insert(['code' => "extra_{$index}", 'name' => "Extra {$index}", 'level' => 50 + $index, 'is_system' => false, 'created_at' => now(), 'updated_at' => now()]);
    }
    peopleWithRole(10, 'agent');

    expect(queriesOf('/api/v1/roles'))->toBe($few);
});
