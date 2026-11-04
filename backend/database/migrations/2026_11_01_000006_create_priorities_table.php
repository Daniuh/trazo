<?php

declare(strict_types=1);

// Catálogo de prioridades. Nunca se borra: se desactiva. Los colores viven en el diseño, no aquí.
// Documentado en docs/base-de-datos/diccionario/catalogos.md

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('priorities', function (Blueprint $table): void {
            $table->tinyIncrements('id');
            $table->string('code', 30)->unique();
            $table->string('name', 50);
            // Orden de gravedad: ordenar por prioridad es ordenar por este valor
            $table->unsignedTinyInteger('level')->unique();
            // Base para sugerir la fecha de vencimiento
            $table->unsignedInteger('default_resolution_minutes');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::statement('ALTER TABLE priorities ADD CONSTRAINT chk_priorities_level CHECK (level >= 1)');
        DB::statement('ALTER TABLE priorities ADD CONSTRAINT chk_priorities_resolution CHECK (default_resolution_minutes > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('priorities');
    }
};
