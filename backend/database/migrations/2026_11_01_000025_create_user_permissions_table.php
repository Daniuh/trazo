<?php

declare(strict_types=1);

// Excepciones de permisos por persona, encima de su rol: concedidos (grant) o quitados (deny). Si chocan, gana el
// quitado. Documentado en docs/base-de-datos/diccionario/roles-y-permisos.md

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_permissions', function (Blueprint $table): void {
            // La excepción es parte de la cuenta: se va con ella
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedSmallInteger('permission_id');
            $table->string('effect', 5);
            // Quién la dio; no se borra a quien administró
            $table->unsignedBigInteger('granted_by');
            $table->timestamp('created_at')->useCurrent();

            $table->primary(['user_id', 'permission_id']);
            $table->foreign('permission_id')->references('id')->on('permissions')->restrictOnDelete();
            $table->foreign('granted_by')->references('id')->on('users')->restrictOnDelete();
            $table->index('permission_id');
            $table->index('granted_by');
        });

        DB::statement("ALTER TABLE user_permissions ADD CONSTRAINT user_permissions_effect CHECK (effect IN ('grant', 'deny'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('user_permissions');
    }
};
