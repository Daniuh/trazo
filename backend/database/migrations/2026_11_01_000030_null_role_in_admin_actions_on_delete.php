<?php

declare(strict_types=1);

// Un rol creado se puede eliminar si nadie lo tiene. Con RESTRICT, su propio historial (creado, editado) lo impediría:
// al eliminarlo, el registro conserva la fila y queda sin enlace; el código y el nombre del rol viajan siempre en el
// detalle. Documentado en docs/base-de-datos/diccionario/admin-actions.md

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_actions', function (Blueprint $table): void {
            $table->dropForeign(['target_role_id']);
            $table->foreign('target_role_id')->references('id')->on('roles')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('admin_actions', function (Blueprint $table): void {
            $table->dropForeign(['target_role_id']);
            $table->foreign('target_role_id')->references('id')->on('roles')->restrictOnDelete();
        });
    }
};
