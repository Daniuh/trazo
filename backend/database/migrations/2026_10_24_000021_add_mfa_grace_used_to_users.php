<?php

declare(strict_types=1);

// Cuántos ingresos ha pospuesto cada persona la configuración del MFA. Se compara con grace_logins de su política.
// Documentado en docs/base-de-datos/diccionario/users.md

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedTinyInteger('mfa_grace_used')->default(0)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('mfa_grace_used');
        });
    }
};
