<?php

declare(strict_types=1);

// Catálogos con ETag y lista de personas asignables.
// Documentado en docs/backend/incidents.md

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('entrega prioridades y estados con sus transiciones', function (): void {
    actingAsDemo('solicitante1@trazo.test');

    $response = $this->getJson('/api/v1/catalogs')->assertOk();

    expect(array_column($response->json('data.priorities'), 'code'))->toBe(['low', 'medium', 'high', 'critical'])
        ->and($response->json('data.statuses.0'))->toMatchArray(['code' => 'open', 'is_initial' => true, 'next' => ['in_progress', 'cancelled']]);
});

it('responde 304 si los catálogos no cambiaron', function (): void {
    actingAsDemo('solicitante1@trazo.test');
    $etag = $this->getJson('/api/v1/catalogs')->headers->get('ETag');

    $this->getJson('/api/v1/catalogs', ['If-None-Match' => $etag])->assertStatus(304);
});

it('lista a quién se puede asignar: activos que atienden incidentes', function (): void {
    actingAsDemo('supervisor@trazo.test');

    $names = array_column($this->getJson('/api/v1/incidents/assignees')->assertOk()->json('data'), 'name');

    expect($names)->toContain('Laura Gómez')->not->toContain('Juan Pérez');
});

it('la lista de asignables es solo para quien puede asignar', function (): void {
    actingAsDemo('agente1@trazo.test');

    $this->getJson('/api/v1/incidents/assignees')->assertForbidden();
});
