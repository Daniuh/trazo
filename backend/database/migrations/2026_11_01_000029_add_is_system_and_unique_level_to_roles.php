<?php

declare(strict_types=1);

// Roles de sistema (los cinco base, que no se borran) y nivel único, para que el orden de la jerarquía nunca sea
// ambiguo. Los roles que ya existen se marcan aquí. Documentado en docs/base-de-datos/diccionario/roles-y-permisos.md

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const SYSTEM_ROLES = ['requester', 'agent', 'supervisor', 'admin', 'super_admin'];

    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table): void {
            $table->boolean('is_system')->default(false)->after('level');
            $table->unique('level');
        });

        DB::table('roles')->whereIn('code', self::SYSTEM_ROLES)->update(['is_system' => true]);
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table): void {
            $table->dropUnique(['level']);
            $table->dropColumn('is_system');
        });
    }
};
