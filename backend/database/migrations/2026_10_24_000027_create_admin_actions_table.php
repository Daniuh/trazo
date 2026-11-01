<?php

declare(strict_types=1);

// Registro de lo que hace la administración: quién, qué, sobre quién o sobre qué rol, y desde dónde.
// Solo de escritura, como el historial de incidentes. Documentado en docs/base-de-datos/diccionario/admin-actions.md

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_actions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedTinyInteger('admin_action_type_id');
            $table->unsignedBigInteger('target_user_id')->nullable();
            $table->unsignedSmallInteger('target_role_id')->nullable();
            // Lo que cambió, con sus valores anteriores y nuevos
            $table->json('details')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('admin_action_type_id')->references('id')->on('admin_action_types')->restrictOnDelete();
            $table->foreign('target_user_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('target_role_id')->references('id')->on('roles')->restrictOnDelete();

            // Qué le hicieron a una persona, y qué hizo cada administrador, en orden
            $table->index(['target_user_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['admin_action_type_id', 'created_at']);
            $table->index('target_role_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_actions');
    }
};
