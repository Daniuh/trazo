<?php

declare(strict_types=1);

// Catálogo de tipos de actividad del historial (creado, cambio de estado, reasignado…).
// Documentado en docs/base-de-datos/diccionario/catalogos.md

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_types', function (Blueprint $table): void {
            $table->tinyIncrements('id');
            $table->string('code', 30)->unique();
            $table->string('name', 50);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_types');
    }
};
