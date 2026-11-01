<?php

declare(strict_types=1);

// Qué roles tiene cada usuario.
// Documentado en docs/base-de-datos/diccionario/roles-y-permisos.md

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_role', function (Blueprint $table): void {
            // Solo aplica si el usuario puede borrarse, es decir, si no tiene historial
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            // No se puede borrar un rol que todavía tienen usuarios
            $table->unsignedSmallInteger('role_id');
            $table->foreign('role_id')->references('id')->on('roles')->restrictOnDelete();

            $table->primary(['user_id', 'role_id']);
            $table->index('role_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_role');
    }
};
