<?php

declare(strict_types=1);

// Catálogo de acciones administrativas (crear un usuario, cambiar su rol, restablecer su MFA…).
// Documentado en docs/base-de-datos/diccionario/admin-actions.md

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_action_types', function (Blueprint $table): void {
            $table->tinyIncrements('id');
            $table->string('code', 40)->unique();
            $table->string('name', 80);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_action_types');
    }
};
