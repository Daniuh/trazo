<?php

declare(strict_types=1);

// Códigos de recuperación de contraseña: solo su HMAC (6 dígitos con un hash simple se descifran en segundos),
// intentos, vencimiento y el pase de un solo uso que entrega el código ya verificado.
// Documentado en docs/base-de-datos/diccionario/password-reset-codes.md

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('password_reset_codes', function (Blueprint $table): void {
            $table->id();
            // Una solicitud es parte de la cuenta: se va con ella
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->char('code_hash', 64);
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->char('ticket_hash', 64)->nullable();
            $table->timestamp('ticket_expires_at')->nullable();
            // Usado o anulado (por un código nuevo o por intentos): ya no sirve
            $table->timestamp('used_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            // La solicitud vigente de una persona: la más reciente sin usar
            $table->index(['user_id', 'used_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_codes');
    }
};
