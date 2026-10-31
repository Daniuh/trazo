---
title: Roles y permisos
description: Tablas del control de acceso por roles con permisos granulares.
audience: tecnico
area: base-de-datos
module: users
order: 11
---

# Roles y permisos

El control de acceso es propio, sin relaciones polimórficas. El código siempre consulta **permisos**, nunca nombres de rol; un rol es solo un grupo de permisos.

## roles

| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | SMALLINT UNSIGNED | PK | |
| `code` | VARCHAR(60) | UNIQUE, NOT NULL | `requester`, `agent`, `supervisor`, `super_admin` |
| `name` | VARCHAR(80) | NOT NULL | Nombre visible |
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

PK `(user_id, role_id)` e índice en `role_id`.

## Matriz inicial

| Permiso | Solicitante | Agente | Supervisor | Super admin |
|---|:-:|:-:|:-:|:-:|
| `incidents.view.own` | ✓ | ✓ | ✓ | ✓ |
| `incidents.view.assigned` | | ✓ | ✓ | ✓ |
| `incidents.view.unassigned` | | ✓ | ✓ | ✓ |
| `incidents.view.any` | | | ✓ | ✓ |
| `incidents.create` | ✓ | ✓ | ✓ | ✓ |
| `incidents.update.own` | ✓ | ✓ | ✓ | ✓ |
| `incidents.update.any` | | | ✓ | ✓ |
| `incidents.take` | | ✓ | ✓ | ✓ |
| `incidents.assign` | | | ✓ | ✓ |
| `incidents.status.assigned` | | ✓ | ✓ | ✓ |
| `incidents.status.any` | | | ✓ | ✓ |
| `incidents.delete`, `incidents.restore` | | | ✓ | ✓ |
| `incidents.export` | | | ✓ | ✓ |
| `dashboard.view.own` | ✓ | ✓ | | |
| `dashboard.view.any` | | | ✓ | ✓ |
| `audit.view` | | | ✓ | ✓ |
| `users.view`, `users.manage`, `roles.manage` | | | | ✓ |
| `system.logs.view`, `system.health.view`, `system.actions.execute` | | | | ✓ |

## Dónde está en el código

- Migraciones: [`000002`](../../../backend/database/migrations/2026_10_21_000002_create_roles_table.php) a [`000005`](../../../backend/database/migrations/2026_10_21_000005_create_user_role_table.php)
- Modelos: [`Role.php`](../../../backend/app/Modules/Users/Infrastructure/Models/Role.php) y [`Permission.php`](../../../backend/app/Modules/Users/Infrastructure/Models/Permission.php)
- Matriz: [`AccessControlSeeder.php`](../../../backend/database/seeders/AccessControlSeeder.php)
- Decisión: [ADR-0021](../../decisiones/0021-permisos-granulares.md)
