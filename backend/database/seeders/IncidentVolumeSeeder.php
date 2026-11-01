<?php

declare(strict_types=1);

// Incidentes de demostración en volumen (por defecto 600; se cambia con SEED_INCIDENTS). En producción solo con
// SEED_INCIDENTS_IN_PRODUCTION=true, repartidos entre las cuentas por rol que existan.
// Cada uno trae su historial completo: creación, asignación y cambios de estado (ver IncidentStory).
// Idempotente: completa hasta llegar al volumen pedido y no toca lo que ya existe. Inserta por lotes, sin Eloquent,
// para poder cargar 100.000 registros en poco tiempo. Documentado en docs/base-de-datos/seeders.md

namespace Database\Seeders;

use App\Modules\Incidents\Infrastructure\IncidentCodes;
use Database\Seeders\Support\DemoRandom;
use Database\Seeders\Support\IncidentSamples;
use Database\Seeders\Support\IncidentStory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * @phpstan-type Context array{priorities: array<string, object>, statuses: array<string, object>, workers: list<int>, managers: list<int>, people: list<int>, names: array<int, array{id: string, name: string}>, types: array<string, int>}
 * @phpstan-type Step array{type: string, user: int, at: Carbon, from?: string, to?: string, assignee?: int, note?: string}
 */
class IncidentVolumeSeeder extends Seeder
{
    private const CHUNK = 1000;

    // Días de historia que cubren los incidentes de demostración
    private const HISTORY_DAYS = 60;

    // De los que ya debieron atenderse, cuántos siguen abiertos (y vencidos)
    private const STUCK_PERCENT = 2;

    private const WORK_PERMISSION = 'incidents.status.assigned';

    private const MANAGE_PERMISSION = 'incidents.assign';

    /** @var array<string, list<Step>> Historial de cada incidente del lote, por su código temporal */
    private array $stories = [];

    public function run(): void
    {
        $missing = (int) config('trazo.seed_incidents') - DB::table('incidents')->count();
        $allowed = ! app()->isProduction() || (bool) config('trazo.seed_incidents_in_production');
        if (! $allowed || $missing <= 0) {
            return;
        }

        // Semilla fija: los mismos datos en cada máquina
        DemoRandom::seed(2026);
        $context = $this->context();

        // Sin quien atienda o asigne no hay historial coherente (en producción, si las cuentas por rol no se crearon)
        if ($context['workers'] === [] || $context['managers'] === []) {
            $this->command?->warn('Incidentes de demostración omitidos: no hay personas activas que los atiendan y asignen.');

            return;
        }

        for ($done = 0; $done < $missing; $done += self::CHUNK) {
            $rows = array_map(fn (): array => $this->row($context), range(1, min(self::CHUNK, $missing - $done)));
            DB::transaction(fn () => $this->insert($rows, $context));
        }
    }

    /**
     * Catálogos y personas que se reparten entre los incidentes.
     *
     * @return Context
     */
    private function context(): array
    {
        $active = DB::table('users')->where('is_active', true)->get(['id', 'ulid', 'name']);

        return [
            'priorities' => DB::table('priorities')->get()->keyBy('code')->all(),
            'statuses' => DB::table('statuses')->get()->keyBy('code')->all(),
            'workers' => $this->usersWith(self::WORK_PERMISSION),
            'managers' => $this->usersWith(self::MANAGE_PERMISSION),
            'people' => array_values($active->map(fn (object $user): int => (int) $user->id)->all()),
            // Igual que el historial real: cada persona se guarda con su id público y su nombre de ese momento
            'names' => $active->mapWithKeys(fn (object $user): array => [
                (int) $user->id => ['id' => (string) $user->ulid, 'name' => (string) $user->name],
            ])->all(),
            'types' => DB::table('activity_types')->pluck('id', 'code')->map(fn (mixed $id): int => (int) $id)->all(),
        ];
    }

    /**
     * Personas activas con un permiso.
     *
     * @return list<int>
     */
    private function usersWith(string $permission): array
    {
        return array_values(DB::table('users')
            ->join('user_role', 'user_role.user_id', '=', 'users.id')
            ->join('role_permission', 'role_permission.role_id', '=', 'user_role.role_id')
            ->join('permissions', 'permissions.id', '=', 'role_permission.permission_id')
            ->where('permissions.code', $permission)
            ->where('users.is_active', true)
            ->distinct()
            ->pluck('users.id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all());
    }

    /**
     * Un incidente coherente con un flujo parejo: llegan a lo largo de los últimos días y su estado depende de la edad.
     * Lo reciente sigue abierto; lo viejo ya se atendió, salvo unos pocos que quedaron atascados (y vencidos).
     * Así lo creado y lo resuelto por día se parecen, como en una mesa de ayuda real.
     *
     * @param  Context  $context
     * @return array<string, mixed>
     */
    private function row(array $context): array
    {
        $priority = $context['priorities'][IncidentSamples::weighted(IncidentSamples::PRIORITY_WEIGHTS)];
        $resolution = (int) $priority->default_resolution_minutes;

        $now = Carbon::now();
        $age = DemoRandom::numberBetween(30, self::HISTORY_DAYS * 1440);
        $createdAt = $now->copy()->subMinutes($age);
        $stillOpen = $age <= $resolution * 1.6 || DemoRandom::boolean(self::STUCK_PERCENT);
        $status = $context['statuses'][IncidentSamples::weighted($stillOpen ? IncidentSamples::PENDING_WEIGHTS : IncidentSamples::FINISHED_WEIGHTS)];

        $dueDate = $createdAt->copy()->addMinutes((int) round($resolution * DemoRandom::randomFloat(2, 0.8, 1.6)));
        $resolvedAt = $status->is_pending || $status->code === 'cancelled'
            ? null
            : $createdAt->copy()->addMinutes(min(DemoRandom::numberBetween(20, $resolution), $age - 1));
        $assigned = $status->is_initial && DemoRandom::boolean(60) ? null : DemoRandom::randomElement($context['workers']);
        $createdBy = DemoRandom::randomElement($context['people']);

        $steps = IncidentStory::steps([
            'status' => (string) $status->code,
            'created_at' => $createdAt,
            'resolved_at' => $resolvedAt,
            'assignee' => $assigned,
            'now' => $now,
        ], $context['managers']);
        $code = 'NEW-'.Str::lower(Str::random(16));
        $this->stories[$code] = [['type' => 'created', 'user' => $createdBy, 'at' => $createdAt], ...$steps];
        $last = $steps === [] ? null : $steps[count($steps) - 1];

        return [
            'ulid' => Str::lower((string) Str::ulid()),
            'code' => $code,
            'title' => IncidentSamples::title(),
            'description' => IncidentSamples::description(),
            'priority_id' => $priority->id,
            'status_id' => $status->id,
            'created_by' => $createdBy,
            'assigned_to' => $assigned,
            'due_date' => $dueDate,
            'resolved_at' => $resolvedAt,
            'lock_version' => 1,
            'created_at' => $createdAt,
            // La última vez que alguien lo tocó
            'updated_at' => $last['at'] ?? $createdAt,
        ];
    }

    /**
     * Inserta el lote con su historial y le asigna su código definitivo.
     *
     * @param  list<array<string, mixed>>  $rows
     * @param  Context  $context
     */
    private function insert(array $rows, array $context): void
    {
        DB::table('incidents')->insert($rows);

        $ids = DB::table('incidents')->where('code', 'like', 'NEW-%')->pluck('id', 'code');
        $activities = [];
        foreach ($ids as $code => $id) {
            foreach ($this->stories[$code] ?? [] as $step) {
                $activities[] = [
                    'incident_id' => $id,
                    'user_id' => $step['user'],
                    'activity_type_id' => $context['types'][$step['type']],
                    'changes' => $this->changes($step, $context),
                    'note' => $step['note'] ?? null,
                    'created_at' => $step['at'],
                ];
            }
        }
        $this->stories = [];

        // Por partes: un solo INSERT con todo el lote pasaría el límite de parámetros de MySQL
        foreach (array_chunk($activities, self::CHUNK) as $chunk) {
            DB::table('incident_activities')->insert($chunk);
        }

        // El mismo formato y contador que la aplicación (TZ-26-0142), por año de creación
        IncidentCodes::fromConfig()->assignPending('NEW-');
    }

    /**
     * Lo que cambió en un paso, con la misma forma que guardan AssignIncident y ChangeIncidentStatus.
     *
     * @param  Step  $step
     * @param  Context  $context
     */
    private function changes(array $step, array $context): ?string
    {
        $status = fn (string $code): array => ['code' => $code, 'name' => $context['statuses'][$code]->name];

        $changes = match ($step['type']) {
            'assigned' => ['assignee' => ['from' => null, 'to' => $context['names'][$step['assignee'] ?? 0] ?? null]],
            'status_changed' => ['status' => ['from' => $status($step['from'] ?? ''), 'to' => $status($step['to'] ?? '')]],
            default => null,
        };

        return $changes === null ? null : json_encode($changes, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }
}
