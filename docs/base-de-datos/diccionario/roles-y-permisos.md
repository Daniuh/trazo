---
title: Roles y permisos
description: Tablas del control de acceso por roles con permisos granulares, nivel en la jerarquía y excepciones por persona.
audience: tecnico
area: base-de-datos
module: users
order: 11
---

# Roles y permisos

El control de acceso es propio, sin relaciones polimórficas. El código siempre consulta **permisos**, nunca nombres de rol: un rol es un grupo de permisos con un nivel en la jerarquía. Encima del rol, cada persona puede tener permisos concedidos o quitados.

**Permisos efectivos** = los del rol + los concedidos − los quitados (si chocan, gana el quitado).

## roles

| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | SMALLINT UNSIGNED | PK | |
| `code` | VARCHAR(60) | UNIQUE, NOT NULL | `requester`, `agent`, `supervisor`, `admin`, `super_admin` |
| `name` | VARCHAR(80) | NOT NULL | Nombre visible |
| `level` | TINYINT UNSIGNED | NOT NULL, UNIQUE, CHECK `BETWEEN 1 AND 100` | Nivel en la jerarquía: solo se administra a quien tiene un nivel menor. 100 es la cima (Super admin). Único, para que el orden nunca sea ambiguo |
| `is_system` | BOOLEAN | NOT NULL, por defecto `false` | Uno de los cinco roles base: no se elimina (sí se edita) |
| `created_at`, `updated_at` | TIMESTAMP | | |

## permissions

| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | SMALLINT UNSIGNED | PK | |
| `code` | VARCHAR(60) | UNIQUE, NOT NULL | Formato `módulo.acción[.alcance]` |
| `name` | VARCHAR(80) | NOT NULL | Nombre visible |
| `created_at`, `updated_at` | TIMESTAMP | | |

## role_permission

| Columna | Referencia | ON DELETE |
|---|---|---|
| `role_id` | `roles.id` | CASCADE |
| `permission_id` | `permissions.id` | RESTRICT |

PK `(role_id, permission_id)` e índice en `permission_id`.

## user_role

| Columna | Referencia | ON DELETE |
|---|---|---|
| `user_id` | `users.id` | CASCADE |
| `role_id` | `roles.id` | RESTRICT |

PK `(user_id, role_id)` e índice en `role_id`. La tabla admite varios roles, pero la administración asigna uno por persona ([ADR-0053](../../decisiones/0053-administracion-de-usuarios.md)).

## user_permissions

Excepciones de permisos de una persona, encima de su rol.

| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `user_id` | BIGINT UNSIGNED | FK → `users.id`, CASCADE | Persona; la excepción se va con la cuenta |
| `permission_id` | SMALLINT UNSIGNED | FK → `permissions.id`, RESTRICT | Permiso concedido o quitado |
| `effect` | VARCHAR(5) | NOT NULL, CHECK `IN ('grant', 'deny')` | `grant`: lo tiene aunque su rol no lo traiga; `deny`: no lo tiene aunque su rol lo traiga |
| `granted_by` | BIGINT UNSIGNED | FK → `users.id`, RESTRICT | Quién dio la excepción; no se borra a quien administró |
| `created_at` | TIMESTAMP | NOT NULL | |

PK `(user_id, permission_id)`, e índices en `permission_id` y `granted_by`. Una persona tiene a lo sumo una excepción por permiso.

## Matriz inicial

| Permiso | Solicitante | Agente | Supervisor | Administrador | Super admin |
|---|:-:|:-:|:-:|:-:|:-:|
| `incidents.view.own`, `incidents.create`, `incidents.update.own` | ✓ | ✓ | ✓ | ✓ | ✓ |
| `incidents.view.assigned`, `incidents.view.unassigned`, `incidents.take`, `incidents.status.assigned` | | ✓ | ✓ | | ✓ |
| `incidents.view.any`, `incidents.update.any`, `incidents.assign`, `incidents.status.any` | | | ✓ | | ✓ |
| `incidents.delete`, `incidents.restore`, `incidents.export` | | | ✓ | | ✓ |
| `dashboard.view.own` | ✓ | ✓ | | ✓ | |
| `dashboard.view.any` | | | ✓ | | ✓ |
| `audit.incidents.view` | | | ✓ | | ✓ |
| `audit.admin.view` | | | | ✓ | ✓ |
| `users.view`, `users.create`, `users.update`, `users.activate`, `users.roles.assign`, `users.permissions.grant` | | | | ✓ | ✓ |
| `users.mfa.reset`, `users.access.reset` | | | | | ✓ |
| `roles.manage` | | | | | ✓ |
| `system.logs.view`, `system.health.view`, `system.actions.execute` | | | | | ✓ |
| **Nivel** | 20 | 40 | 60 | 80 | 100 |

Es la matriz con la que nace cada rol base. Después la administración la puede ajustar desde la pantalla de roles (y crear roles nuevos entre los niveles), y volver a sembrar no la revierte ([ADR-0054](../../decisiones/0054-matriz-de-permisos-no-destructiva.md)).

## Dónde está en el código

- Migraciones: [`000002`](../../../backend/database/migrations/2026_10_21_000002_create_roles_table.php) a [`000005`](../../../backend/database/migrations/2026_10_21_000005_create_user_role_table.php), [`000023` (nivel)](../../../backend/database/migrations/2026_10_24_000023_add_level_to_roles.php), [`000025` (user_permissions)](../../../backend/database/migrations/2026_10_24_000025_create_user_permissions_table.php), [`000028` (quita `users.manage`)](../../../backend/database/migrations/2026_10_24_000028_remove_users_manage_permission.php) , [`000029` (roles de sistema y nivel único)](../../../backend/database/migrations/2026_10_24_000029_add_is_system_and_unique_level_to_roles.php) y [`000031` (divide `audit.view`)](../../../backend/database/migrations/2026_10_24_000031_split_audit_view_permission.php)
- Modelos: [`Role.php`](../../../backend/app/Modules/Users/Infrastructure/Models/Role.php) y [`Permission.php`](../../../backend/app/Modules/Users/Infrastructure/Models/Permission.php)
- Permisos efectivos: [`UserPermissions.php`](../../../backend/app/Modules/Users/Infrastructure/UserPermissions.php) y [`PermissionSql.php`](../../../backend/app/Modules/Users/Infrastructure/PermissionSql.php)
- Matriz: [`AccessControlSeeder.php`](../../../backend/database/seeders/AccessControlSeeder.php)
- Decisiones: [ADR-0021](../../decisiones/0021-permisos-granulares.md), [ADR-0053](../../decisiones/0053-administracion-de-usuarios.md) , [ADR-0054](../../decisiones/0054-matriz-de-permisos-no-destructiva.md) y [ADR-0056](../../decisiones/0056-auditoria-global.md)
