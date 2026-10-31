<?php

declare(strict_types=1);

// Roles (con su nivel en la jerarquía), permisos granulares y la matriz inicial de qué puede hacer cada rol.
// La matriz solo se siembra para un rol o un permiso que se crea en esta ejecución: lo que la administración ajuste
// después no se revierte al volver a sembrar. Documentado en docs/base-de-datos/seeders.md

namespace Database\Seeders;

use Database\Seeders\Concerns\InsertsMissingRows;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccessControlSeeder extends Seeder
{
    use InsertsMissingRows;

    /** Código => [nombre, nivel] */
    private const ROLES = [
        'requester' => ['Solicitante', 20],
        'agent' => ['Agente', 40],
        'supervisor' => ['Supervisor', 60],
        'admin' => ['Administrador', 80],
        'super_admin' => ['Super admin', 100],
    ];

    private const EVERYONE = ['requester', 'agent', 'supervisor', 'admin', 'super_admin'];

    private const STAFF = ['agent', 'supervisor', 'super_admin'];

    private const MANAGERS = ['supervisor', 'super_admin'];

    private const USER_ADMINS = ['admin', 'super_admin'];

    private const ROOT = ['super_admin'];

    /** Permiso => [nombre, roles que lo tienen al crearse] */
    private const PERMISSIONS = [
        'incidents.view.own' => ['Ver incidentes propios', self::EVERYONE],
        'incidents.view.assigned' => ['Ver incidentes asignados', self::STAFF],
        'incidents.view.unassigned' => ['Ver incidentes sin asignar', self::STAFF],
        'incidents.view.any' => ['Ver todos los incidentes', self::MANAGERS],
        'incidents.create' => ['Crear incidentes', self::EVERYONE],
        'incidents.update.own' => ['Editar incidentes propios', self::EVERYONE],
        'incidents.update.any' => ['Editar cualquier incidente', self::MANAGERS],
        'incidents.take' => ['Tomar incidentes sin asignar', self::STAFF],
        'incidents.assign' => ['Asignar incidentes a otros', self::MANAGERS],
        'incidents.status.assigned' => ['Cambiar estado de incidentes asignados', self::STAFF],
        'incidents.status.any' => ['Cambiar estado de cualquier incidente', self::MANAGERS],
        'incidents.delete' => ['Eliminar incidentes', self::MANAGERS],
        'incidents.restore' => ['Restaurar incidentes', self::MANAGERS],
        'incidents.export' => ['Exportar incidentes', self::MANAGERS],
        'dashboard.view.own' => ['Ver dashboard propio', ['requester', 'agent', 'admin']],
        'dashboard.view.any' => ['Ver dashboard general', self::MANAGERS],
        'audit.view' => ['Ver auditoría global', ['supervisor', 'admin', 'super_admin']],
        'users.view' => ['Ver usuarios', self::USER_ADMINS],
        'users.create' => ['Crear usuarios', self::USER_ADMINS],
        'users.update' => ['Editar nombre y correo de usuarios', self::USER_ADMINS],
        'users.activate' => ['Activar y desactivar usuarios', self::USER_ADMINS],
        'users.roles.assign' => ['Cambiar el rol de usuarios', self::USER_ADMINS],
        'users.permissions.grant' => ['Conceder y quitar permisos a usuarios', self::USER_ADMINS],
        'users.mfa.reset' => ['Restablecer la verificación en dos pasos', self::ROOT],
        'users.access.reset' => ['Restablecer el acceso de usuarios', self::ROOT],
        'roles.manage' => ['Gestionar roles y permisos', self::ROOT],
        'system.logs.view' => ['Ver logs del sistema', self::ROOT],
        'system.health.view' => ['Ver salud del sistema', self::ROOT],
        'system.actions.execute' => ['Ejecutar acciones de sistema', self::ROOT],
    ];

    public function run(): void
    {
        DB::transaction(function (): void {
            $newRoles = $this->missing('roles', array_keys(self::ROLES));
            $newPermissions = $this->missing('permissions', array_keys(self::PERMISSIONS));

            $this->insertMissing('roles', 'code', array_map(
                // Los roles base son de sistema: no se borran
                fn (string $code, array $role): array => ['code' => $code, 'name' => $role[0], 'level' => $role[1], 'is_system' => true],
                array_keys(self::ROLES),
                self::ROLES,
            ));

            $this->insertMissing('permissions', 'code', array_map(
                fn (string $code, array $definition): array => ['code' => $code, 'name' => $definition[0]],
                array_keys(self::PERMISSIONS),
                self::PERMISSIONS,
            ));

            $this->seedMatrix($newRoles, $newPermissions);
        });
    }

    /**
     * Los códigos que todavía no existen en la tabla.
     *
     * @param  list<string>  $codes
     * @return list<string>
     */
    private function missing(string $table, array $codes): array
    {
        return array_values(array_diff($codes, DB::table($table)->pluck('code')->all()));
    }

    /**
     * Solo las parejas en que el rol o el permiso son nuevos: un permiso quitado a mano no vuelve.
     *
     * @param  list<string>  $newRoles
     * @param  list<string>  $newPermissions
     */
    private function seedMatrix(array $newRoles, array $newPermissions): void
    {
        $roleIds = DB::table('roles')->pluck('id', 'code');
        $permissionIds = DB::table('permissions')->pluck('id', 'code');
        $rows = [];

        foreach (self::PERMISSIONS as $permission => [, $roles]) {
            foreach ($roles as $role) {
                if (in_array($role, $newRoles, true) || in_array($permission, $newPermissions, true)) {
                    $rows[] = ['role_id' => $roleIds[$role], 'permission_id' => $permissionIds[$permission]];
                }
            }
        }

        if ($rows !== []) {
            DB::table('role_permission')->insert($rows);
        }
    }
}
