<?php

declare(strict_types=1);

// Catálogo de permisos (GET /permissions): quién lo ve, el módulo de cada uno, cuáles son sensibles y cuáles puede
// conceder quien consulta (nadie concede lo que no tiene, salvo la cima). Documentado en docs/backend/users.md

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function permissionCatalog(): array
{
    return collect(test()->getJson('/api/v1/permissions')->assertOk()->json('data'))->keyBy('code')->all();
}

it('trae el módulo, si es sensible y si quien consulta lo puede conceder', function (): void {
    actingAsDemo('admin@trazo.test');
    $catalog = permissionCatalog();

    expect($catalog['incidents.export'])->toMatchArray(['module' => 'incidents', 'sensitive' => false])
        ->and($catalog['users.create'])->toMatchArray(['module' => 'users', 'sensitive' => true, 'grantable' => true])
        // El Administrador no tiene las acciones de sistema: no las puede conceder
        ->and($catalog['system.actions.execute'])->toMatchArray(['module' => 'system', 'sensitive' => true, 'grantable' => false]);
});

it('la cima puede conceder cualquiera', function (): void {
    actingAsDemo('superadmin@trazo.test');

    expect(collect(permissionCatalog())->every(fn (array $permission): bool => $permission['grantable']))->toBeTrue();
});

it('solo lo ve quien administra personas o roles', function (): void {
    actingAsDemo('agente1@trazo.test');

    $this->getJson('/api/v1/permissions')->assertForbidden();
});
