<?php

declare(strict_types=1);

// Notificaciones de cada persona. No copian datos: apuntan al mensaje o al registro del historial que las originó,
// y de ahí salen quién lo hizo y qué cambió. Los mensajes sin leer de un mismo incidente se agrupan en una sola fila
// (occurrences); lo garantiza la columna calculada pending_comment_incident_id.
// Documentado en docs/base-de-datos/diccionario/notifications.md

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table): void {
            $table->id();
            $table->char('ulid', 26)->unique();
            $table->unsignedBigInteger('user_id');
            $table->unsignedTinyInteger('notification_type_id');
            $table->unsignedBigInteger('incident_id');
            $table->unsignedBigInteger('comment_id')->nullable();
            $table->unsignedBigInteger('activity_id')->nullable();
            $table->unsignedSmallInteger('occurrences')->default(1);
            $table->timestamp('read_at')->nullable();
            $table->timestamp('created_at');
            // Igual al incidente mientras es un aviso de mensajes sin leer y NULL después: el índice único admite varios NULL
            $table->unsignedBigInteger('pending_comment_incident_id')
                ->storedAs('IF(read_at IS NULL AND comment_id IS NOT NULL, incident_id, NULL)');

            // Una notificación no tiene sentido sin su destinatario ni sin el historial que la originó. Incidentes y
            // mensajes no se borran de verdad (borrado lógico), y MySQL no admite CASCADE en las columnas de la
            // columna calculada: por eso esos dos van con RESTRICT
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('notification_type_id')->references('id')->on('notification_types')->restrictOnDelete();
            $table->foreign('incident_id')->references('id')->on('incidents')->restrictOnDelete();
            $table->foreign('comment_id')->references('id')->on('incident_comments')->restrictOnDelete();
            $table->foreign('activity_id')->references('id')->on('incident_activities')->cascadeOnDelete();

            // Lista de la persona, de la más nueva a la más vieja; contadores de sin leer; un aviso de mensajes por incidente
            $table->index(['user_id', 'id']);
            $table->index(['user_id', 'read_at']);
            $table->unique(['user_id', 'pending_comment_incident_id']);
            $table->index('created_at');
        });

        DB::statement('ALTER TABLE notifications ADD CONSTRAINT chk_notifications_source CHECK (comment_id IS NULL OR activity_id IS NULL)');
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
