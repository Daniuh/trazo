<?php

declare(strict_types=1);

// Qué permisos tiene cada rol.
// Documentado en docs/base-de-datos/diccionario/roles-y-permisos.md

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_permission', function (Blueprint $table): void {
            // Mismo tipo que la clave a la que apuntan (SMALLINT UNSIGNED)
            $table->unsignedSmallInteger('role_id');
            $table->unsignedSmallInteger('permission_id');

            // Si el rol desaparece, sus asignaciones no tienen sentido
            $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
            // No se puede borrar un permiso que algún rol todavía tiene
            $table->foreign('permission_id')->references('id')->on('permissions')->restrictOnDelete();

            $table->primary(['role_id', 'permission_id']);
            $table->index('permission_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permission');
    }
};
