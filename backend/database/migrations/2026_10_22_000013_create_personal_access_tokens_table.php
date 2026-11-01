<?php

declare(strict_types=1);

// Tokens de sesión de Sanctum. Solo los usuarios tienen tokens, así que tokenable_id es una FK real a users.
// Documentado en docs/base-de-datos/diccionario/personal-access-tokens.md

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_access_tokens', function (Blueprint $table): void {
            $table->id();
            // Sanctum consulta por tipo; el tipo está fijado a 'user' para que la FK no sea ambigua
            $table->string('tokenable_type', 20);
            // Si el usuario se borra, sus sesiones dejan de existir con él
            $table->foreignId('tokenable_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 100);
            // Solo se guarda el SHA-256 del token; el valor en claro sale una única vez al iniciar sesión
            $table->char('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->dateTime('last_used_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->timestamps();

            $table->index(['tokenable_type', 'tokenable_id']);
            // La limpieza diaria borra por fecha de expiración
            $table->index('expires_at');
        });

        DB::statement("ALTER TABLE personal_access_tokens ADD CONSTRAINT chk_tokens_tokenable_type CHECK (tokenable_type = 'user')");
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
    }
};
