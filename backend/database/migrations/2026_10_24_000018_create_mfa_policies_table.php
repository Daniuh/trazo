<?php

declare(strict_types=1);

// Política de MFA por rol, guardada como dato: si es obligatorio y cuántos ingresos se puede posponer.
// Con varios roles se aplica la regla más estricta. Documentado en docs/base-de-datos/diccionario/mfa.md

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mfa_policies', function (Blueprint $table): void {
            // Una política por rol; si el rol se elimina, su política no tiene sentido
            $table->unsignedSmallInteger('role_id')->primary();
            $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
            $table->boolean('is_required');
            $table->unsignedTinyInteger('grace_logins');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mfa_policies');
    }
};
