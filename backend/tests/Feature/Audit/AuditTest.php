<?php

declare(strict_types=1);

// Auditoría global por la API: permisos de cada pestaña, alcance por jerarquía (igual al del dominio), rango por
// defecto, filtros, cursor sin saltos ni repetidos, enlaces según lo que quien mira puede abrir y sin N+1.
// Documentado en docs/backend/audit.md

use App\Modules\Audit\Domain\AdminAuditScope;
use App\Modules\Incidents\Infrastructure\Models\Incident;
use App\Modules\Users\Infrastructure\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    cache()->flush();
});

function auditViewer(string $email): User
{
    app('auth')->forgetGuards();

    return actingAsDemo($email);
}

function demoId(string $email): int
{
    return (int) User::query()->where('email', $email)->value('id');
}

// Un registro del historial de un incidente, con la fecha que haga falta
function activityOf(Incident $incident, string $email, string $type, string $createdAt = 'now', ?array $changes = null): int
{
    return (int) DB::table('incident_activities')->insertGetId([
        'incident_id' => $incident->id,
        'user_id' => demoId($email),
        'activity_type_id' => DB::table('activity_types')->where('code', $type)->value('id'),
        'changes' => $changes === null ? null : json_encode($changes),
        'ip_address' => '203.0.113.7',
        'user_agent' => 'Navegador de prueba',
        'created_at' => (new DateTimeImmutable($createdAt))->format('Y-m-d H:i:s'),
    ]);
}

// Una acción administrativa sobre una persona, un rol existente o nada (rol eliminado)
function adminActionBy(string $email, string $type, ?string $target = null, ?string $role = null, array $details = [], string $createdAt = 'now'): int
{
    return (int) DB::table('admin_actions')->insertGetId([
        'user_id' => demoId($email),
        'admin_action_type_id' => DB::table('admin_action_types')->where('code', $type)->value('id'),
        'target_user_id' => $target === null ? null : demoId($target),
        'target_role_id' => $role === null ? null : DB::table('roles')->where('code', $role)->value('id'),
        'details' => $details === [] ? null : json_encode($details),
        'ip_address' => '203.0.113.8',
        'user_agent' => 'Navegador de prueba',
        'created_at' => (new DateTimeImmutable($createdAt))->format('Y-m-d H:i:s'),
    ]);
}

// El código del primer error de un campo anidado (su nombre lleva puntos, así que no sirve la ruta de assertJsonPath)
function fieldError(TestResponse $response, string $field): ?string
{
    return $response->assertUnprocessable()->json('errors')[$field][0]['code'] ?? null;
}

/**
 * @return list<int>
 */
function auditIds(string $tab, string $query = ''): array
{
    return array_column(test()->getJson("/api/v1/audit/{$tab}?{$query}")->assertOk()->json('data'), 'id');
}

describe('permisos', function (): void {
    it('cada pestaña exige su permiso y nadie más la ve', function (string $email, int $incidents, int $admin): void {
        auditViewer($email);

        $this->getJson('/api/v1/audit/incidents')->assertStatus($incidents);
        $this->getJson('/api/v1/audit/admin')->assertStatus($admin);
    })->with([
        'solicitante' => ['solicitante1@trazo.test', 403, 403],
        'agente' => ['agente1@trazo.test', 403, 403],
        'supervisor' => ['supervisor@trazo.test', 200, 403],
        'administrador' => ['admin@trazo.test', 403, 200],
        'super admin' => ['superadmin@trazo.test', 200, 200],
    ]);

    it('el catálogo trae solo lo de las pestañas que se pueden ver', function (): void {
        auditViewer('supervisor@trazo.test');
        $this->getJson('/api/v1/audit/catalog')->assertOk()
            ->assertJsonPath('data.admin_types', null)
            ->assertJsonPath('data.roles', [])
            ->assertJsonPath('data.incident_types.0.code', 'created');

        auditViewer('admin@trazo.test');
        $this->getJson('/api/v1/audit/catalog')->assertOk()
            ->assertJsonPath('data.incident_types', null)
            ->assertJsonPath('data.roles.0.code', 'super_admin')
            ->assertJsonCount(DB::table('users')->count(), 'data.people');

        auditViewer('agente1@trazo.test');
        $this->getJson('/api/v1/audit/catalog')->assertForbidden();
    });

    it('el permiso se puede conceder a una persona puntual', function (): void {
        DB::table('user_permissions')->insert([
            'user_id' => demoId('agente1@trazo.test'),
            'permission_id' => DB::table('permissions')->where('code', 'audit.incidents.view')->value('id'),
            'effect' => 'grant',
            'granted_by' => demoId('superadmin@trazo.test'),
        ]);
        auditViewer('agente1@trazo.test');

        $this->getJson('/api/v1/audit/incidents')->assertOk();
    });
});

describe('incidentes', function (): void {
    it('muestra todo el historial, también el de eliminados, con IP y navegador', function (): void {
        $deleted = Incident::factory()->create(['deleted_at' => now()]);
        $id = activityOf($deleted, 'supervisor@trazo.test', 'deleted');
        auditViewer('superadmin@trazo.test');

        $this->getJson('/api/v1/audit/incidents')->assertOk()
            ->assertJsonPath('data.0.id', $id)
            ->assertJsonPath('data.0.type.code', 'deleted')
            ->assertJsonPath('data.0.incident.deleted', true)
            ->assertJsonPath('data.0.incident.code', $deleted->code)
            ->assertJsonPath('data.0.ip_address', '203.0.113.7')
            ->assertJsonPath('data.0.user_agent', 'Navegador de prueba');
    });

    it('sin rango son los últimos 30 días; con rango, el que se pida', function (): void {
        $incident = Incident::factory()->create();
        $recent = activityOf($incident, 'supervisor@trazo.test', 'updated', '-2 days');
        $old = activityOf($incident, 'supervisor@trazo.test', 'created', '-40 days');
        auditViewer('supervisor@trazo.test');

        expect(auditIds('incidents'))->toBe([$recent]);
        $from = urlencode((new DateTimeImmutable('-60 days'))->format(DATE_ATOM));
        expect(auditIds('incidents', "filter[from]={$from}"))->toBe([$recent, $old]);
        $this->getJson('/api/v1/audit/incidents')->assertJsonStructure(['meta' => ['next_cursor', 'from', 'to']]);
    });

    it('filtra por quién hizo el cambio, por tipo y por el código del incidente escrito de cualquier forma', function (): void {
        $first = Incident::factory()->create();
        $second = Incident::factory()->create();
        $bySupervisor = activityOf($first, 'supervisor@trazo.test', 'created');
        $byAgent = activityOf($second, 'agente1@trazo.test', 'status_changed');
        auditViewer('supervisor@trazo.test');
        $agent = User::query()->where('email', 'agente1@trazo.test')->value('ulid');
        // Sin el prefijo y en minúsculas, como lo escribiría una persona (00-123456)
        $typed = strtolower(substr($second->code, 3));

        expect(auditIds('incidents', "filter[actor]={$agent}"))->toBe([$byAgent])
            ->and(auditIds('incidents', 'filter[type]=created,updated'))->toBe([$bySupervisor])
            ->and(auditIds('incidents', "filter[incident]={$typed}"))->toBe([$byAgent])
            ->and(auditIds('incidents', 'filter[incident]=TZ-99-9999'))->toBe([])
            ->and(auditIds('incidents', 'filter[actor]=01ARZ3NDEKTSV4RRFFQ69G5FAV'))->toBe([]);
        expect(fieldError($this->getJson('/api/v1/audit/incidents?filter[incident]=abc'), 'filter.incident'))->toBe('INCIDENT_CODE_FORMAT');
    });

    it('el enlace al incidente aparece solo si quien mira puede abrirlo', function (): void {
        DB::table('user_permissions')->insert([
            'user_id' => demoId('agente1@trazo.test'),
            'permission_id' => DB::table('permissions')->where('code', 'audit.incidents.view')->value('id'),
            'effect' => 'grant',
            'granted_by' => demoId('superadmin@trazo.test'),
        ]);
        $mine = Incident::factory()->create(['assigned_to' => demoId('agente1@trazo.test')]);
        $others = Incident::factory()->create(['assigned_to' => demoId('agente2@trazo.test')]);
        activityOf($mine, 'supervisor@trazo.test', 'assigned', '-2 minutes');
        activityOf($others, 'supervisor@trazo.test', 'assigned', '-1 minute');
        auditViewer('agente1@trazo.test');

        $this->getJson('/api/v1/audit/incidents')->assertOk()
            ->assertJsonPath('data.0.links.incident', false)
            ->assertJsonPath('data.1.links.incident', true);
    });
});

describe('cursor', function (): void {
    it('recorre todo sin saltos ni repetidos, aunque varios registros tengan la misma fecha', function (): void {
        $incident = Incident::factory()->create();
        $ids = [];
        foreach (range(1, 7) as $i) {
            // Tres con la misma fecha: el desempate es el id
            $ids[] = activityOf($incident, 'supervisor@trazo.test', 'updated', $i <= 3 ? '-1 hour' : "-{$i} hours");
        }
        auditViewer('supervisor@trazo.test');

        $seen = [];
        $cursor = null;
        do {
            $response = $this->getJson('/api/v1/audit/incidents?per_page=2'.($cursor ? "&cursor={$cursor}" : ''))->assertOk();
            $seen = [...$seen, ...array_column($response->json('data'), 'id')];
            $cursor = $response->json('meta.next_cursor');
        } while ($cursor !== null);

        $expected = DB::table('incident_activities')->orderByDesc('created_at')->orderByDesc('id')->pluck('id')->all();
        expect($seen)->toBe($expected)->toHaveCount(7);
    });

    it('rechaza un cursor alterado y un rango al revés', function (): void {
        auditViewer('supervisor@trazo.test');

        $this->getJson('/api/v1/audit/incidents?cursor=hola')->assertUnprocessable()
            ->assertJsonPath('errors.cursor.0.code', 'INVALID_CURSOR');
        expect(fieldError($this->getJson('/api/v1/audit/incidents?filter[from]=2026-10-10&filter[to]=2026-10-01'), 'filter.to'))
            ->toBe('DATE_TOO_EARLY');
    });
});

describe('administración', function (): void {
    beforeEach(function (): void {
        $this->rows = [
            'propia' => adminActionBy('admin@trazo.test', 'user_updated', 'agente1@trazo.test'),
            'sobre un agente' => adminActionBy('superadmin@trazo.test', 'user_deactivated', 'agente2@trazo.test'),
            'sobre el administrador' => adminActionBy('superadmin@trazo.test', 'user_role_changed', 'admin@trazo.test'),
            'sobre el super admin' => adminActionBy('superadmin@trazo.test', 'user_updated', 'superadmin@trazo.test'),
            'rol por debajo' => adminActionBy('superadmin@trazo.test', 'role_updated', role: 'supervisor', details: ['code' => 'supervisor']),
            'rol de la cima' => adminActionBy('superadmin@trazo.test', 'role_updated', role: 'super_admin', details: ['code' => 'super_admin']),
            'rol eliminado por debajo' => adminActionBy('superadmin@trazo.test', 'role_deleted', details: ['code' => 'soporte', 'name' => 'Soporte', 'level' => 50]),
            'rol eliminado por encima' => adminActionBy('superadmin@trazo.test', 'role_deleted', details: ['code' => 'jefe', 'name' => 'Jefe', 'level' => 90]),
        ];
    });

    it('el administrador ve lo propio y lo que toca a quien está por debajo; la cima ve todo', function (): void {
        auditViewer('admin@trazo.test');
        $visible = ['propia', 'sobre un agente', 'rol por debajo', 'rol eliminado por debajo'];
        expect(auditIds('admin'))->toEqualCanonicalizing(array_values(array_intersect_key($this->rows, array_flip($visible))));

        auditViewer('superadmin@trazo.test');
        expect(auditIds('admin'))->toEqualCanonicalizing(array_values($this->rows));
    });

    it('la regla en SQL coincide con la del dominio para cada registro', function (): void {
        $admin = User::query()->where('email', 'admin@trazo.test')->firstOrFail();
        $scope = AdminAuditScope::below($admin->id, 80);
        $userLevel = fn (int $id): int => (int) DB::table('user_role')->join('roles', 'roles.id', '=', 'user_role.role_id')
            ->where('user_role.user_id', $id)->max('roles.level');

        $expected = DB::table('admin_actions')->get()->filter(function (object $row) use ($scope, $userLevel): bool {
            $details = json_decode((string) ($row->details ?? 'null'), true);
            $level = match (true) {
                $row->target_user_id !== null => $userLevel((int) $row->target_user_id),
                $row->target_role_id !== null => (int) DB::table('roles')->where('id', $row->target_role_id)->value('level'),
                is_int($details['level'] ?? null) => $details['level'],
                default => null,
            };

            return $scope->includes((int) $row->user_id, $level);
        })->pluck('id')->all();

        auditViewer('admin@trazo.test');
        expect(auditIds('admin'))->toEqualCanonicalizing($expected);
    });

    it('un rol eliminado se muestra con el código y el nombre guardados, sin enlace', function (): void {
        auditViewer('superadmin@trazo.test');
        $entry = collect($this->getJson('/api/v1/audit/admin?filter[role]=soporte')->assertOk()->json('data'))->sole();

        expect($entry['target_role'])->toBe(['code' => 'soporte', 'name' => 'Soporte', 'deleted' => true])
            ->and($entry['links'])->toBe(['user' => false, 'role' => false]);
    });

    it('filtra por persona afectada, por rol y por tipo', function (): void {
        auditViewer('superadmin@trazo.test');
        $agent = User::query()->where('email', 'agente2@trazo.test')->value('ulid');

        expect(auditIds('admin', "filter[user]={$agent}"))->toBe([$this->rows['sobre un agente']])
            ->and(auditIds('admin', 'filter[role]=supervisor'))->toBe([$this->rows['rol por debajo']])
            ->and(auditIds('admin', 'filter[type]=role_deleted'))->toEqualCanonicalizing([
                $this->rows['rol eliminado por debajo'], $this->rows['rol eliminado por encima'],
            ]);
    });

    it('el enlace a la ficha sigue los permisos de quien mira', function (): void {
        auditViewer('superadmin@trazo.test');
        $this->getJson('/api/v1/audit/admin?filter[role]=supervisor')->assertJsonPath('data.0.links', ['user' => false, 'role' => true]);

        auditViewer('admin@trazo.test');
        $this->getJson('/api/v1/audit/admin?filter[role]=supervisor')->assertJsonPath('data.0.links', ['user' => false, 'role' => false]);
        $agent = User::query()->where('email', 'agente2@trazo.test')->value('ulid');
        $this->getJson("/api/v1/audit/admin?filter[user]={$agent}")->assertJsonPath('data.0.links', ['user' => true, 'role' => false]);
    });
});

it('hace las mismas consultas con pocos o muchos registros (sin N+1)', function (): void {
    $count = function (int $entries): int {
        DB::table('incident_activities')->delete();
        foreach (range(1, $entries) as $i) {
            activityOf(Incident::factory()->create(), $i % 2 ? 'supervisor@trazo.test' : 'agente1@trazo.test', 'created');
        }
        auditViewer('superadmin@trazo.test');
        DB::flushQueryLog();
        DB::enableQueryLog();
        test()->getJson('/api/v1/audit/incidents?per_page=50')->assertOk();
        DB::disableQueryLog();

        return count(DB::getQueryLog());
    };

    // La primera petición también lee permisos, que luego quedan en memoria
    $count(1);
    expect($count(2))->toBe($count(20));
});
