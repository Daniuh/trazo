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
