<?php

declare(strict_types=1);

// "Ver auditoría global" se dividió en la auditoría de incidentes y la de administración, para que cada rol vea solo
// lo suyo. Esta es la parte "contract": se quita el permiso viejo de los roles, de las excepciones por persona y del
// catálogo. Los dos nuevos los crea el seeder. Documentado en docs/base-de-datos/diccionario/roles-y-permisos.md

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $id = DB::table('permissions')->where('code', 'audit.view')->value('id');
        if ($id === null) {
            return;
        }

        DB::transaction(function () use ($id): void {
            DB::table('role_permission')->where('permission_id', $id)->delete();
            DB::table('user_permissions')->where('permission_id', $id)->delete();
            DB::table('permissions')->where('id', $id)->delete();
        });
    }

    public function down(): void
    {
        // Sin vuelta atrás: el seeder ya no lo crea y los dos permisos nuevos lo reemplazan
    }
};
