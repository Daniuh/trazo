<?php

declare(strict_types=1);

// Diseño del dashboard: original por rol, guardar, validar en el servidor, restaurar y quién puede entrar.
// Documentado en docs/backend/dashboard.md

use App\Modules\Users\Infrastructure\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

/**
 * @return array<string, array<string, mixed>>
 */
function dashboardWidgets(): array
{
    return collect(test()->getJson('/api/v1/dashboard')->assertOk()->json('data.widgets'))->keyBy('code')->all();
}

/**
 * @param  array<string, array<string, mixed>>  $widgets
 * @return list<array<string, mixed>>
 */
function layoutPayload(array $widgets): array
{
    return array_values(array_map(
        fn (array $widget): array => array_intersect_key($widget, array_flip(['code', 'x', 'y', 'w', 'h', 'visible'])),
        $widgets,
    ));
}

it('ofrece a cada rol solo los widgets que su permiso alcanza, en el diseño original', function (): void {
    actingAsDemo('solicitante1@trazo.test');
    $requester = $this->getJson('/api/v1/dashboard')->assertOk();

    expect($requester->json('data.scope'))->toBe('personal')
        ->and($requester->json('data.customized'))->toBeFalse()
        ->and(array_column($requester->json('data.widgets'), 'code'))
        ->not->toContain('unassigned_incidents', 'assigned_to_me', 'workload_by_agent');

    actingAsDemo('supervisor@trazo.test');
    $supervisor = $this->getJson('/api/v1/dashboard')->assertOk();

    expect($supervisor->json('data.scope'))->toBe('general')
        ->and($supervisor->json('data.widgets'))->toHaveCount(10)
        ->and($supervisor->json('data.widgets.0'))->toMatchArray(['code' => 'total_incidents', 'visible' => true, 'x' => 0, 'y' => 0]);
});

it('guarda el diseño, conserva el lugar de los ocultos y lo devuelve igual', function (): void {
    actingAsDemo('agente1@trazo.test');
    $widgets = dashboardWidgets();
    $widgets['total_incidents'] = [...$widgets['total_incidents'], 'w' => 2];
    $widgets['due_soon'] = [...$widgets['due_soon'], 'visible' => false];

    $this->putJson('/api/v1/dashboard/layout', ['widgets' => layoutPayload($widgets)])
        ->assertOk()
        ->assertJsonPath('data.customized', true);

    $saved = dashboardWidgets();
    expect($saved['total_incidents']['w'])->toBe(2)
        ->and($saved['due_soon'])->toMatchArray(['visible' => false, 'x' => $widgets['due_soon']['x'], 'y' => $widgets['due_soon']['y']]);
});

it('deja ocultos, debajo de todo, los widgets que no se enviaron', function (): void {
    actingAsDemo('supervisor@trazo.test');

    $this->putJson('/api/v1/dashboard/layout', ['widgets' => [['code' => 'overdue_incidents', 'x' => 0, 'y' => 0, 'w' => 4, 'h' => 2, 'visible' => true]]])
        ->assertOk();

    $saved = dashboardWidgets();
    expect($saved)->toHaveCount(10)
        ->and($saved['overdue_incidents']['visible'])->toBeTrue()
        ->and($saved['total_incidents'])->toMatchArray(['visible' => false, 'y' => 2]);
});

it('valida en el servidor los choques, los topes y los permisos', function (): void {
    actingAsDemo('solicitante1@trazo.test');
    $overlap = [
        ['code' => 'total_incidents', 'x' => 0, 'y' => 0, 'w' => 3, 'h' => 2, 'visible' => true],
        ['code' => 'pending_incidents', 'x' => 2, 'y' => 0, 'w' => 3, 'h' => 2, 'visible' => true],
    ];

    $this->putJson('/api/v1/dashboard/layout', ['widgets' => $overlap])
        ->assertUnprocessable()->assertJsonPath('code', 'WIDGETS_OVERLAP');
    $this->putJson('/api/v1/dashboard/layout', ['widgets' => [['code' => 'total_incidents', 'x' => 0, 'y' => 0, 'w' => 12, 'h' => 2, 'visible' => true]]])
        ->assertUnprocessable()->assertJsonPath('code', 'WIDGET_SIZE_OUT_OF_RANGE')->assertJsonPath('params.max_w', 6);
    // El solicitante no tiene el permiso que exige este widget
    $this->putJson('/api/v1/dashboard/layout', ['widgets' => [['code' => 'workload_by_agent', 'x' => 0, 'y' => 0, 'w' => 6, 'h' => 4, 'visible' => true]]])
        ->assertUnprocessable()->assertJsonPath('code', 'WIDGET_NOT_AVAILABLE');
    $this->putJson('/api/v1/dashboard/layout', ['widgets' => [['code' => 'no_existe', 'x' => 0, 'y' => 0, 'w' => 6, 'h' => 4, 'visible' => true]]])
        ->assertUnprocessable()->assertJsonPath('errors', fn (array $errors): bool => isset($errors['widgets.0.code']));

    expect(DB::table('user_dashboard_widgets')->count())->toBe(0);
});

it('restaura el diseño original borrando lo guardado', function (): void {
    $user = actingAsDemo('supervisor@trazo.test');
    $this->putJson('/api/v1/dashboard/layout', ['widgets' => []])->assertOk();

    $this->deleteJson('/api/v1/dashboard/layout')->assertOk()->assertJsonPath('data.customized', false);

    expect(DB::table('user_dashboard_widgets')->where('user_id', $user->id)->count())->toBe(0);
});

it('exige sesión y el permiso del dashboard', function (): void {
    $this->getJson('/api/v1/dashboard')->assertUnauthorized();

    // Una cuenta sin roles no tiene permisos de dashboard
    Sanctum::actingAs(User::factory()->create());
    $this->getJson('/api/v1/dashboard')->assertForbidden()->assertJsonPath('code', 'FORBIDDEN_ACTION');
    $this->getJson('/api/v1/dashboard/metrics?widgets=total_incidents')->assertForbidden();
});
