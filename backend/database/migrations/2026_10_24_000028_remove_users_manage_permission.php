<?php

declare(strict_types=1);

// "Gestionar usuarios" se dividió en permisos más finos (crear, editar, activar, asignar rol, conceder permisos,
// restablecer MFA y acceso). Esta es la parte "contract": se quita el permiso viejo de donde esté.
// Documentado en docs/base-de-datos/diccionario/roles-y-permisos.md

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $id = DB::table('permissions')->where('code', 'users.manage')->value('id');
        if ($id === null) {
            return;
        }

        DB::table('role_permission')->where('permission_id', $id)->delete();
        DB::table('permissions')->where('id', $id)->delete();
    }

    public function down(): void
    {
        // Sin vuelta atrás: el seeder ya no lo crea y los permisos nuevos lo reemplazan
    }
};
