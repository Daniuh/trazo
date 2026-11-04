<?php

declare(strict_types=1);

// Segundos factores de cada persona. Hoy solo TOTP; la columna `method` deja lugar a otros sin tocar `users`.
// El secreto va cifrado con la clave de la aplicación. Documentado en docs/base-de-datos/diccionario/mfa.md

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_mfa_factors', function (Blueprint $table): void {
            $table->id();
            // El factor es parte de la cuenta: se va con ella
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('method', 20);
            $table->text('secret');
            // Null mientras la persona no confirma con su primer código
            $table->timestamp('confirmed_at')->nullable();
            // Paso de tiempo del último código aceptado: uno igual o anterior se rechaza (sin repetición)
            $table->unsignedBigInteger('last_used_step')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'method']);
        });

        DB::statement("ALTER TABLE user_mfa_factors ADD CONSTRAINT chk_user_mfa_factors_method CHECK (method IN ('totp'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('user_mfa_factors');
    }
};
