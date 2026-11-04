<?php

declare(strict_types=1);

// Conversación de cada incidente: un mensaje por fila, con quién lo escribió. Quien lo escribió puede borrarlo durante
// unos minutos: la fila queda ("Mensaje eliminado") y el texto se vacía. Documentado en
// docs/base-de-datos/diccionario/incident-comments.md

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incident_comments', function (Blueprint $table): void {
            $table->id();
            $table->char('ulid', 26)->unique();
            $table->unsignedBigInteger('incident_id');
            $table->unsignedBigInteger('user_id');
            // Vacío solo cuando el mensaje se borró
            $table->string('body', 2000)->nullable();
            $table->timestamp('created_at');
            $table->timestamp('deleted_at')->nullable();

            // Los mensajes son parte del rastro del incidente: ni él ni las personas se borran con ellos
            $table->foreign('incident_id')->references('id')->on('incidents')->restrictOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();

            // La conversación se lee por incidente, del más nuevo al más viejo, de a páginas
            $table->index(['incident_id', 'id']);
        });

        DB::statement('ALTER TABLE incident_comments ADD CONSTRAINT chk_incident_comments_body CHECK ((deleted_at IS NULL) = (body IS NOT NULL))');
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_comments');
    }
};
