<?php

declare(strict_types=1);

// Usuarios: se desactivan, nunca se borran, para conservar el historial de lo que hicieron.
// Documentado en docs/base-de-datos/diccionario/users.md

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->char('ulid', 26)->unique();
            $table->string('name', 120);
            $table->string('email', 191)->unique();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Selector de "asignar a": solo usuarios activos, ordenados por nombre
            $table->index(['is_active', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
