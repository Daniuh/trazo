<?php

declare(strict_types=1);

// Catálogo de eventos del sistema, con su categoría y severidad. Idempotente: agrega los que falten.
// Documentado en docs/base-de-datos/seeders.md

namespace Database\Seeders;

use Database\Seeders\Concerns\InsertsMissingRows;
use Illuminate\Database\Seeder;

class SystemEventTypeSeeder extends Seeder
{
    use InsertsMissingRows;

    // código => [nombre, categoría, severidad]
    private const TYPES = [
        'unhandled_error' => ['Error no controlado', 'failure', 'critical'],
        'job_failed' => ['Job fallido', 'failure', 'warning'],
        'queue_backlog' => ['Cola acumulada', 'failure', 'warning'],
        'service_down' => ['Servicio sin respuesta', 'infrastructure', 'critical'],
        'external_service_unavailable' => ['Servicio externo sin respuesta', 'infrastructure', 'warning'],
        'login_locked' => ['Ingreso bloqueado por intentos fallidos', 'security', 'warning'],
        'mfa_attempts_exceeded' => ['Ingreso cortado por códigos incorrectos', 'security', 'warning'],
        'reset_code_voided' => ['Código de recuperación anulado por intentos', 'security', 'warning'],
        'reauthentication_locked' => ['Reautenticación bloqueada', 'security', 'critical'],
        'access_denied_repeated' => ['Accesos denegados repetidos', 'security', 'warning'],
    ];

    public function run(): void
    {
        $this->insertMissing('system_event_types', 'code', array_map(
            fn (string $code, array $type): array => ['code' => $code, 'name' => $type[0], 'category' => $type[1], 'severity' => $type[2]],
            array_keys(self::TYPES),
            self::TYPES,
        ));
    }
}
