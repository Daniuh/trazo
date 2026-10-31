<?php

declare(strict_types=1);

// Roles: agrupan permisos. Se identifican por su code, nunca por el id.
// Documentado en docs/base-de-datos/diccionario/roles-y-permisos.md

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table): void {
            $table->smallIncrements('id');
            $table->string('code', 60)->unique();
            $table->string('name', 80);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
