<?php

declare(strict_types=1);

// Permisos granulares con formato módulo.acción[.alcance], p. ej. incidents.view.assigned.
// Documentado en docs/base-de-datos/diccionario/roles-y-permisos.md

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table): void {
            $table->smallIncrements('id');
            $table->string('code', 60)->unique();
            $table->string('name', 80);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
