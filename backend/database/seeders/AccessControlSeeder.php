<?php

declare(strict_types=1);

// Roles, permisos granulares y la matriz aprobada de qué puede hacer cada rol.
// Idempotente y no destructivo: agrega lo que falta y nunca quita permisos que alguien haya ajustado.
// Documentado en docs/base-de-datos/seeders.md

namespace Database\Seeders;

use Database\Seeders\Concerns\InsertsMissingRows;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccessControlSeeder extends Seeder
{
    use InsertsMissingRows;

    private const ROLES = [
        'requester' => 'Solicitante',
        'agent' => 'Agente',
        'supervisor' => 'Supervisor',
        'super_admin' => 'Super admin',
    ];

    private const ALL = ['requester', 'agent', 'supervisor', 'super_admin'];

    private const STAFF = ['agent', 'supervisor', 'super_admin'];

    private const MANAGERS = ['supervisor', 'super_admin'];

    private const ADMIN = ['super_admin'];

    /** Permiso => [nombre, roles que lo tienen] */
    private const PERMISSIONS = [
        'incidents.view.own' => ['Ver incidentes propios', self::ALL],
        'incidents.view.assigned' => ['Ver incidentes asignados', self::STAFF],
        'incidents.view.unassigned' => ['Ver incidentes sin asignar', self::STAFF],
        'incidents.view.any' => ['Ver todos los incidentes', self::MANAGERS],
        'incidents.create' => ['Crear incidentes', self::ALL],
        'incidents.update.own' => ['Editar incidentes propios', self::ALL],
        'incidents.update.any' => ['Editar cualquier incidente', self::MANAGERS],
        'incidents.take' => ['Tomar incidentes sin asignar', self::STAFF],
        'incidents.assign' => ['Asignar incidentes a otros', self::MANAGERS],
        'incidents.status.assigned' => ['Cambiar estado de incidentes asignados', self::STAFF],
        'incidents.status.any' => ['Cambiar estado de cualquier incidente', self::MANAGERS],
        'incidents.delete' => ['Eliminar incidentes', self::MANAGERS],
        'incidents.restore' => ['Restaurar incidentes', self::MANAGERS],
        'incidents.export' => ['Exportar incidentes', self::MANAGERS],
        'dashboard.view.own' => ['Ver dashboard propio', ['requester', 'agent']],
        'dashboard.view.any' => ['Ver dashboard general', self::MANAGERS],
        'audit.view' => ['Ver auditoría global', self::MANAGERS],
        'users.view' => ['Ver usuarios', self::ADMIN],
        'users.manage' => ['Gestionar usuarios', self::ADMIN],
        'roles.manage' => ['Gestionar roles y permisos', self::ADMIN],
        'system.logs.view' => ['Ver logs del sistema', self::ADMIN],
        'system.health.view' => ['Ver salud del sistema', self::ADMIN],
        'system.actions.execute' => ['Ejecutar acciones de sistema', self::ADMIN],
    ];

    public function run(): void
    {
        $this->insertMissing('roles', 'code', array_map(
            fn (string $code, string $name): array => ['code' => $code, 'name' => $name],
            array_keys(self::ROLES),
            self::ROLES,
        ));

        $this->insertMissing('permissions', 'code', array_map(
            fn (string $code, array $definition): array => ['code' => $code, 'name' => $definition[0]],
            array_keys(self::PERMISSIONS),
            self::PERMISSIONS,
        ));

        $this->seedMatrix();
    }

    private function seedMatrix(): void
    {
        $roleIds = DB::table('roles')->pluck('id', 'code');
        $permissionIds = DB::table('permissions')->pluck('id', 'code');
        $rows = [];

        foreach (self::PERMISSIONS as $permission => [, $roles]) {
            foreach ($roles as $role) {
                $rows[] = ['role_id' => $roleIds[$role], 'permission_id' => $permissionIds[$permission]];
            }
        }

        DB::table('role_permission')->insertOrIgnore($rows);
    }
}
