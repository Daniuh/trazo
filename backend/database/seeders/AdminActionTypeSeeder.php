<?php

declare(strict_types=1);

// Catálogo de acciones administrativas que se registran en admin_actions. Idempotente: agrega las que falten.
// Documentado en docs/base-de-datos/seeders.md

namespace Database\Seeders;

use Database\Seeders\Concerns\InsertsMissingRows;
use Illuminate\Database\Seeder;

class AdminActionTypeSeeder extends Seeder
{
    use InsertsMissingRows;

    private const TYPES = [
        'user_created' => 'Usuario creado',
        'user_updated' => 'Datos de usuario editados',
        'user_activated' => 'Usuario activado',
        'user_deactivated' => 'Usuario desactivado',
        'user_role_changed' => 'Rol de usuario cambiado',
        'user_permissions_changed' => 'Permisos de usuario cambiados',
        'user_mfa_reset' => 'Verificación en dos pasos restablecida',
        'user_access_reset' => 'Acceso de usuario restablecido',
        'role_created' => 'Rol creado',
        'role_updated' => 'Rol editado',
        'role_deleted' => 'Rol eliminado',
        'incidents_exported' => 'Incidentes exportados',
        'system_workers_restarted' => 'Workers reiniciados',
        'system_realtime_restarted' => 'Tiempo real reiniciado',
        'system_cache_cleared' => 'Caché de datos limpiada',
        'system_maintenance_enabled' => 'Modo mantenimiento activado',
        'system_maintenance_disabled' => 'Modo mantenimiento desactivado',
        'system_jobs_retried' => 'Jobs fallidos reintentados',
        'system_job_discarded' => 'Job fallido descartado',
    ];

    public function run(): void
    {
        $this->insertMissing('admin_action_types', 'code', array_map(
            fn (string $code, string $name): array => ['code' => $code, 'name' => $name],
            array_keys(self::TYPES),
            self::TYPES,
        ));
    }
}
