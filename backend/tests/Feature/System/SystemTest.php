<?php

declare(strict_types=1);

// Pantalla de sistema: permisos, salud, eventos (agrupación, reapertura, filtros y estado), acciones con
// reautenticación y registro, caché de datos sin tocar la de seguridad, mantenimiento y jobs fallidos sin su
// contenido. Documentado en docs/backend/system.md

use App\Modules\Shared\Application\Ports\SystemEvents;
use App\Modules\Shared\Application\SystemEventType;
use App\Modules\System\Application\Data\HealthMeasurements;
use App\Modules\System\Application\Ports\HealthProbe;
use App\Modules\Users\Infrastructure\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

// Cada prueba parte sin eventos (otras pruebas pueden haber dejado alguno confirmado fuera de su transacción)
beforeEach(function (): void {
    cache()->flush();
    DB::table('system_events')->delete();
});

function systemOperator(string $email = 'superadmin@trazo.test'): User
{
    app('auth')->forgetGuards();

    return actingAsDemo($email);
}

// Un código válido aunque ya se haya usado otro en esta misma ventana de 30 segundos (cada llamada deja un secreto
// nuevo, sin pasos usados)
function systemCode(User $user): string
{
    return currentCode(activeMfaSecret($user));
}

function fakeHealth(array $overrides = []): void
{
    $values = [
        'at' => new DateTimeImmutable('2026-10-25 12:00:00'),
        'databaseMs' => 3.2,
        'redisMs' => 1.1,
        'pendingJobs' => 0,
        'oldestPendingSeconds' => null,
        'failedJobs' => 0,
        'workerSecondsAgo' => 20,
        'schedulerSecondsAgo' => 30,
        'realtimeReachable' => true,
        'diskFreeBytes' => 50_000,
        'diskTotalBytes' => 100_000,
        ...$overrides,
    ];
    app()->instance(HealthProbe::class, new class(new HealthMeasurements(...$values)) implements HealthProbe
    {
        public function __construct(private HealthMeasurements $measurements) {}

        public function measure(): HealthMeasurements
        {
            return $this->measurements;
        }
    });
}

function recordEvent(SystemEventType $type, string $source, string $message = 'Algo pasó', array $context = []): void
{
    app(SystemEvents::class)->record($type, $source, $message, $context);
}

function failedJob(string $payloadSecret = 'dato-sensible'): string
{
    $uuid = (string) Str::uuid();
    DB::table('failed_jobs')->insert([
        'uuid' => $uuid,
        'connection' => 'redis',
        'queue' => 'default',
        'payload' => json_encode(['displayName' => 'App\\Mail\\PasswordResetCode', 'data' => ['code' => $payloadSecret]]),
        'exception' => "RuntimeException: SMTP no respondió\n#0 /var/www/html/vendor/...",
        'failed_at' => now(),
    ]);

    return $uuid;
}

describe('permisos', function (): void {
    it('solo quien tiene los permisos de sistema entra', function (string $method, string $uri): void {
        systemOperator('admin@trazo.test');

        $this->json($method, $uri, ['enabled' => true, 'all' => true, 'status' => 'resolved'])->assertForbidden();
    })->with([
        ['GET', '/api/v1/system/health'],
        ['GET', '/api/v1/system/events'],
        ['GET', '/api/v1/system/events/types'],
        ['PATCH', '/api/v1/system/events/1'],
        ['GET', '/api/v1/system/failed-jobs'],
        ['POST', '/api/v1/system/failed-jobs/retry'],
        ['POST', '/api/v1/system/actions/clear-cache'],
        ['PUT', '/api/v1/system/maintenance'],
    ]);
});

describe('salud', function (): void {
    it('interpreta cada medición y el estado general es el peor', function (): void {
        fakeHealth(['redisMs' => null, 'pendingJobs' => 150, 'workerSecondsAgo' => null]);
        systemOperator();

        $response = $this->getJson('/api/v1/system/health')->assertOk();
        $components = collect($response->json('data.components'))->keyBy('code');

        expect($response->json('data.status'))->toBe('failing')
            ->and($components['database']['status'])->toBe('ok')
            ->and($components['redis']['status'])->toBe('failing')
            ->and($components['queue'])->toMatchArray(['status' => 'warning', 'pending' => 150])
            // Sin señal todavía: atención, no falla
            ->and($components['worker']['status'])->toBe('warning')
            ->and($components['storage']['status'])->toBe('ok')
            ->and($response->json('data.maintenance'))->toBeNull();
    });
});

describe('eventos', function (): void {
    it('agrupa por huella, suma al contador y reabre lo resuelto', function (): void {
        recordEvent(SystemEventType::JobFailed, 'SendMail|RuntimeException', 'Primera');
        recordEvent(SystemEventType::JobFailed, 'SendMail|RuntimeException', 'Segunda');
        recordEvent(SystemEventType::JobFailed, 'Otro|RuntimeException');
        expect(DB::table('system_events')->count())->toBe(2);

        $event = DB::table('system_events')->where('message', 'Segunda')->first();
        expect($event->occurrences)->toBe(2);

        $operator = systemOperator();
        $this->patchJson("/api/v1/system/events/{$event->id}", ['status' => 'resolved'])
            ->assertOk()
            ->assertJsonPath('data.status', 'resolved')
            ->assertJsonPath('data.status_changed_by', $operator->name);

        recordEvent(SystemEventType::JobFailed, 'SendMail|RuntimeException', 'Volvió');
        $again = DB::table('system_events')->find($event->id);
        expect($again->status)->toBe('open')
            ->and($again->occurrences)->toBe(3)
            ->and($again->status_changed_by)->toBeNull();
    });

    it('lista de lo más reciente a lo más antiguo, con filtros y cursor', function (): void {
        $this->travelTo('2026-10-25 10:00:00');
        recordEvent(SystemEventType::LoginLocked, 'a', 'Viejo');
        $this->travelTo('2026-10-25 11:00:00');
        recordEvent(SystemEventType::ServiceDown, 'redis', 'Medio');
        $this->travelTo('2026-10-25 12:00:00');
        recordEvent(SystemEventType::JobFailed, 'b', 'Nuevo');
        systemOperator();

        $first = $this->getJson('/api/v1/system/events?per_page=2')->assertOk();
        expect(array_column($first->json('data'), 'message'))->toBe(['Nuevo', 'Medio']);

        $cursor = $first->json('meta.next_cursor');
        $this->getJson("/api/v1/system/events?per_page=2&cursor={$cursor}")
            ->assertJsonPath('data.0.message', 'Viejo')
            ->assertJsonPath('meta.next_cursor', null);

        $this->getJson('/api/v1/system/events?filter[severity]=critical')->assertJsonCount(1, 'data')->assertJsonPath('data.0.type.code', 'service_down');
        $this->getJson('/api/v1/system/events?filter[type]=login_locked,job_failed')->assertJsonCount(2, 'data');
        $this->getJson('/api/v1/system/events?filter[status]=resolved')->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/system/events?filter[status]=closed')->assertUnprocessable();
        $this->getJson('/api/v1/system/events?cursor=no-es-un-cursor')->assertUnprocessable()->assertJsonPath('errors.cursor.0.code', 'INVALID_CURSOR');
    });

    it('entrega el catálogo de tipos y responde 404 si el evento no existe', function (): void {
        systemOperator();

        $this->getJson('/api/v1/system/events/types')->assertOk()->assertJsonFragment(['code' => 'unhandled_error', 'severity' => 'critical']);
        $this->patchJson('/api/v1/system/events/999999', ['status' => 'resolved'])->assertNotFound()->assertJsonPath('code', 'SYSTEM_EVENT_NOT_FOUND');
    });

    it('borra solo los resueltos más viejos que la retención', function (): void {
        recordEvent(SystemEventType::JobFailed, 'viejo');
        recordEvent(SystemEventType::JobFailed, 'reciente');
        recordEvent(SystemEventType::JobFailed, 'abierto');
        DB::table('system_events')->where('message', 'Algo pasó')->whereIn('fingerprint', [
            hash('sha256', 'job_failed|viejo'), hash('sha256', 'job_failed|reciente'),
        ])->update(['status' => 'resolved', 'status_changed_at' => now()->subDays(10)]);
        DB::table('system_events')->where('fingerprint', hash('sha256', 'job_failed|viejo'))->update(['status_changed_at' => now()->subDays(91)]);

        $this->artisan('system:prune-events')->assertSuccessful();

        expect(DB::table('system_events')->count())->toBe(2);
    });
});

describe('fuentes de eventos', function (): void {
    it('un error no controlado queda como evento, sin la consulta de un error de base de datos', function (): void {
        Route::middleware('api')->get('/api/v1/__boom', fn () => DB::select('select * from tabla_que_no_existe where email = ?', ['secreto@trazo.test']));

        $this->getJson('/api/v1/__boom')->assertStatus(500)->assertJsonPath('code', 'INTERNAL_ERROR');

        $event = DB::table('system_events')->first();
        expect($event->message)->toStartWith('Error de base de datos (SQLSTATE')
            ->and($event->context)->not->toContain('secreto@trazo.test')
            ->and($event->trace_id)->not->toBeNull();
    });

    it('una regla de negocio no se registra como falla', function (): void {
        systemOperator('agente1@trazo.test');

        $this->getJson('/api/v1/incidents/export')->assertForbidden();

        expect(DB::table('system_events')->count())->toBe(0);
    });

    it('un login bloqueado deja el evento con la cuenta enmascarada', function (): void {
        foreach (range(1, 5) as $attempt) {
            $this->postJson('/api/v1/auth/login', ['email' => 'agente1@trazo.test', 'password' => 'equivocada']);
        }

        $event = DB::table('system_events')->first();
        expect($event->message)->toContain('5 intentos')
            ->and(json_decode($event->context, true)['account'])->toBe('ag***@trazo.test');
    });

    it('la reautenticación bloqueada y los accesos denegados repetidos dejan su evento una vez', function (): void {
        $operator = systemOperator();
        systemCode($operator);
        foreach (range(1, 5) as $attempt) {
            $this->postJson('/api/v1/system/actions/clear-cache', ['mfa_code' => '000000']);
        }
        expect(DB::table('system_events')->join('system_event_types as t', 't.id', '=', 'system_event_type_id')->value('t.code'))
            ->toBe('reauthentication_locked');

        systemOperator('agente1@trazo.test');
        foreach (range(1, 12) as $attempt) {
            $this->getJson('/api/v1/system/health')->assertForbidden();
        }
        $denied = DB::table('system_events')->where('fingerprint', hash('sha256', 'access_denied_repeated|user:'.$this->app['auth']->guard('sanctum')->id()))->first();
        expect($denied?->occurrences)->toBe(1);
    });
});

describe('acciones', function (): void {
    it('piden el código de la aplicación y quedan registradas', function (): void {
        $operator = systemOperator();

        $this->postJson('/api/v1/system/actions/restart-workers')->assertUnprocessable()->assertJsonPath('code', 'REAUTHENTICATION_REQUIRED');
        $this->postJson('/api/v1/system/actions/restart-workers', ['mfa_code' => systemCode($operator)])->assertNoContent();

        expect(cache()->get('illuminate:queue:restart'))->not->toBeNull()
            ->and(DB::table('admin_actions')->join('admin_action_types as t', 't.id', '=', 'admin_action_type_id')->pluck('t.code')->all())
            ->toBe(['system_workers_restarted']);
    });

    it('limpiar la caché de datos no borra bloqueos ni límites', function (): void {
        $operator = systemOperator();
        cache()->put('dashboard:generation', 4);
        cache()->put('login:bloqueo-de-prueba', 'sigue');

        $this->postJson('/api/v1/system/actions/clear-cache', ['mfa_code' => systemCode($operator)])->assertNoContent();

        expect(cache()->get('dashboard:generation'))->toBe(5)
            ->and(cache()->get('login:bloqueo-de-prueba'))->toBe('sigue');
    });

    it('permite 5 acciones por minuto', function (): void {
        systemOperator();

        foreach (range(1, 5) as $attempt) {
            $this->postJson('/api/v1/system/actions/restart-realtime')->assertUnprocessable();
        }

        $this->postJson('/api/v1/system/actions/restart-realtime')->assertTooManyRequests();
    });

    it('rechaza una acción que no existe', function (): void {
        systemOperator();

        $this->postJson('/api/v1/system/actions/drop-database')->assertNotFound();
    });
});

describe('mantenimiento', function (): void {
    it('deja pasar solo a quien ejecuta acciones de sistema, y el ingreso sigue abierto', function (): void {
        $operator = systemOperator();
        $this->putJson('/api/v1/system/maintenance', ['enabled' => true, 'mfa_code' => systemCode($operator)])->assertNoContent();
        $this->putJson('/api/v1/system/maintenance', ['enabled' => true, 'mfa_code' => systemCode($operator)])
            ->assertConflict()->assertJsonPath('code', 'MAINTENANCE_ALREADY_ENABLED');

        $this->getJson('/api/v1/incidents')->assertOk()->assertHeader('X-Maintenance', 'on');
        $this->getJson('/api/v1/system/health')->assertJsonPath('data.maintenance.enabled_by', $operator->name);

        systemOperator('admin@trazo.test');
        $this->getJson('/api/v1/incidents')->assertStatus(503)->assertJsonPath('code', 'SERVICE_UNAVAILABLE');
        $this->getJson('/api/v1/auth/me')->assertStatus(503);

        app('auth')->forgetGuards();
        $this->postJson('/api/v1/auth/login', ['email' => 'admin@trazo.test', 'password' => 'equivocada'])->assertUnauthorized();
        $this->get('/up')->assertOk();

        systemOperator();
        $this->putJson('/api/v1/system/maintenance', ['enabled' => false, 'mfa_code' => systemCode($operator)])->assertNoContent();
        systemOperator('admin@trazo.test');
        $this->getJson('/api/v1/incidents')->assertOk()->assertHeaderMissing('X-Maintenance');
    });
});

describe('jobs fallidos', function (): void {
    it('se listan sin su contenido', function (): void {
        failedJob('dato-que-no-debe-salir');
        systemOperator();

        $response = $this->getJson('/api/v1/system/failed-jobs')->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.job', 'PasswordResetCode')
            ->assertJsonPath('data.0.error', 'RuntimeException: SMTP no respondió');

        expect($response->getContent())->not->toContain('dato-que-no-debe-salir');
    });

    it('se reintentan y se descartan con reautenticación', function (): void {
        Queue::fake();
        $first = failedJob();
        $second = failedJob();
        $operator = systemOperator();

        $this->postJson('/api/v1/system/failed-jobs/retry', ['ids' => [$first], 'mfa_code' => systemCode($operator)])
            ->assertOk()->assertJsonPath('data.retried', 1);
        $this->deleteJson("/api/v1/system/failed-jobs/{$second}", ['mfa_code' => systemCode($operator)])->assertNoContent();

        expect(DB::table('failed_jobs')->count())->toBe(0);
        $this->postJson('/api/v1/system/failed-jobs/retry', ['all' => true, 'mfa_code' => systemCode($operator)])
            ->assertConflict()->assertJsonPath('code', 'NO_FAILED_JOBS');
        $this->deleteJson("/api/v1/system/failed-jobs/{$second}", ['mfa_code' => systemCode($operator)])
            ->assertNotFound()->assertJsonPath('code', 'FAILED_JOB_NOT_FOUND');
    });
});

describe('revisión de cada minuto', function (): void {
    it('registra las piezas caídas y la cola acumulada, y deja la señal del scheduler', function (): void {
        fakeHealth(['redisMs' => null, 'realtimeReachable' => false, 'workerSecondsAgo' => 600, 'pendingJobs' => 250]);

        $this->artisan('system:check')->assertSuccessful();

        $codes = DB::table('system_events')->join('system_event_types as t', 't.id', '=', 'system_event_type_id')
            ->orderBy('system_events.id')->pluck('t.code')->all();
        expect($codes)->toBe(['service_down', 'service_down', 'service_down', 'queue_backlog'])
            ->and(cache()->get('system:heartbeat:scheduler'))->not->toBeNull()
            // En pruebas la cola es síncrona: el job de la señal del worker ya corrió
            ->and(cache()->get('system:heartbeat:worker'))->not->toBeNull();
    });
});
