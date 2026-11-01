<?php

declare(strict_types=1);

// Códigos de recuperación del MFA: solo su hash, y cuándo se usó cada uno (sirven una sola vez).
// Documentado en docs/base-de-datos/diccionario/mfa.md

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_recovery_codes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->char('code_hash', 64);
            $table->timestamp('used_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            // Se busca por persona y hash al canjear un código
            $table->unique(['user_id', 'code_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_recovery_codes');
    }
};
