<?php

declare(strict_types=1);

// Incidentes de demostración en volumen (por defecto 600; se cambia con SEED_INCIDENTS). Nunca en producción.
// Idempotente: completa hasta llegar al volumen pedido y no toca lo que ya existe. Inserta por lotes, sin Eloquent,
// para poder cargar 100.000 registros en segundos. Documentado en docs/base-de-datos/seeders.md

namespace Database\Seeders;

use Database\Seeders\Support\IncidentSamples;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class IncidentVolumeSeeder extends Seeder
{
    private const CHUNK = 1000;

    // Días de historia que cubren los incidentes de demostración
    private const HISTORY_DAYS = 60;

    // De los que ya debieron atenderse, cuántos siguen abiertos (y vencidos)
    private const STUCK_PERCENT = 2;

    private const WORK_PERMISSION = 'incidents.status.assigned';

    public function run(): void
    {
        $missing = (int) config('trazo.seed_incidents') - DB::table('incidents')->count();
        if (app()->isProduction() || $missing <= 0) {
            return;
        }

        // Semilla fija: los mismos datos en cada máquina
        fake()->seed(2026);
        $context = $this->context();

        for ($done = 0; $done < $missing; $done += self::CHUNK) {
            $rows = array_map(fn (): array => $this->row($context), range(1, min(self::CHUNK, $missing - $done)));
            DB::transaction(fn () => $this->insert($rows, $context['created_type']));
        }
    }

    /**
     * Catálogos y personas que se reparten entre los incidentes.
     *
     * @return array{priorities: array<string, object>, statuses: array<string, object>, workers: list<int>, people: list<int>, created_type: int}
     */
    private function context(): array
    {
        $workers = DB::table('users')
            ->join('user_role', 'user_role.user_id', '=', 'users.id')
            ->join('role_permission', 'role_permission.role_id', '=', 'user_role.role_id')
            ->join('permissions', 'permissions.id', '=', 'role_permission.permission_id')
            ->where('permissions.code', self::WORK_PERMISSION)
            ->where('users.is_active', true)
            ->distinct()
            ->pluck('users.id');

        return [
            'priorities' => DB::table('priorities')->get()->keyBy('code')->all(),
            'statuses' => DB::table('statuses')->get()->keyBy('code')->all(),
            'workers' => array_values($workers->map(fn (mixed $id): int => (int) $id)->all()),
            'people' => array_values(DB::table('users')->where('is_active', true)->pluck('id')->map(fn (mixed $id): int => (int) $id)->all()),
            'created_type' => (int) DB::table('activity_types')->where('code', 'created')->value('id'),
        ];
    }

    /**
     * Un incidente coherente con un flujo parejo: llegan a lo largo de los últimos días y su estado depende de la edad.
     * Lo reciente sigue abierto; lo viejo ya se atendió, salvo unos pocos que quedaron atascados (y vencidos).
     * Así lo creado y lo resuelto por día se parecen, como en una mesa de ayuda real.
     *
     * @param  array{priorities: array<string, object>, statuses: array<string, object>, workers: list<int>, people: list<int>, created_type: int}  $context
     * @return array<string, mixed>
     */
    private function row(array $context): array
    {
        $priority = $context['priorities'][IncidentSamples::weighted(IncidentSamples::PRIORITY_WEIGHTS)];
        $resolution = (int) $priority->default_resolution_minutes;

        $age = fake()->numberBetween(30, self::HISTORY_DAYS * 1440);
        $createdAt = Carbon::now()->subMinutes($age);
        $stillOpen = $age <= $resolution * 1.6 || fake()->boolean(self::STUCK_PERCENT);
        $status = $context['statuses'][IncidentSamples::weighted($stillOpen ? IncidentSamples::PENDING_WEIGHTS : IncidentSamples::FINISHED_WEIGHTS)];

        $dueDate = $createdAt->copy()->addMinutes((int) round($resolution * fake()->randomFloat(2, 0.8, 1.6)));
        $resolvedAt = $status->is_pending || $status->code === 'cancelled'
            ? null
            : $createdAt->copy()->addMinutes(min(fake()->numberBetween(20, $resolution), $age - 1));
        $assigned = $status->is_initial && fake()->boolean(60) ? null : fake()->randomElement($context['workers']);

        return [
            'ulid' => Str::lower((string) Str::ulid()),
            'code' => 'NEW-'.Str::lower(Str::random(16)),
            'title' => IncidentSamples::title(),
            'description' => IncidentSamples::description(),
            'priority_id' => $priority->id,
            'status_id' => $status->id,
            'created_by' => fake()->randomElement($context['people']),
            'assigned_to' => $assigned,
            'due_date' => $dueDate,
            'resolved_at' => $resolvedAt,
            'lock_version' => 1,
            'created_at' => $createdAt,
            'updated_at' => $resolvedAt ?? $createdAt,
        ];
    }

    /**
     * Inserta el lote, registra su creación en el historial y le asigna su código definitivo.
     *
     * @param  list<array<string, mixed>>  $rows
     */
    private function insert(array $rows, int $createdType): void
    {
        DB::table('incidents')->insert($rows);

        $new = DB::table('incidents')->where('code', 'like', 'NEW-%')->get(['id', 'created_by', 'created_at']);
        DB::table('incident_activities')->insert($new->map(fn (object $incident): array => [
            'incident_id' => $incident->id,
            'user_id' => $incident->created_by,
            'activity_type_id' => $createdType,
            'created_at' => $incident->created_at,
        ])->all());

        // El mismo formato que la aplicación (INC-000123), sin recortar ids de más de seis cifras
        DB::table('incidents')->where('code', 'like', 'NEW-%')
            ->update(['code' => DB::raw("CONCAT('INC-', IF(id > 999999, id, LPAD(id, 6, '0')))")]);
    }
}
