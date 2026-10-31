<?php

declare(strict_types=1);

// Catálogo de estados del ciclo de vida. Las banderas describen su comportamiento sin depender del nombre.
// Documentado en docs/base-de-datos/diccionario/catalogos.md

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('statuses', function (Blueprint $table): void {
            $table->tinyIncrements('id');
            $table->string('code', 30)->unique();
            $table->string('name', 50);
            $table->unsignedTinyInteger('sort_order');
            // Estado con el que nace un incidente
            $table->boolean('is_initial')->default(false);
            // Trabajo pendiente: cuenta para "vencido"
            $table->boolean('is_pending')->default(false);
            // Fin del ciclo de vida
            $table->boolean('is_final')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('statuses');
    }
};
