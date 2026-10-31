---
title: admin_actions
description: Registro de las acciones administrativas y su catálogo de tipos.
audience: tecnico
area: base-de-datos
module: users
order: 19
---

# admin_actions

Registro de lo que hace la administración: quién, qué, sobre quién o sobre qué rol, y desde dónde. Solo de escritura, como el historial de incidentes. Lo usan la administración de usuarios y, más adelante, las acciones de sistema.

## admin_action_types

Catálogo de tipos de acción.

| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | TINYINT UNSIGNED | PK | |
| `code` | VARCHAR(40) | UNIQUE, NOT NULL | `user_created`, `user_updated`, `user_activated`, `user_deactivated`, `user_role_changed`, `user_permissions_changed`, `user_mfa_reset`, `user_access_reset`, `role_created`, `role_updated`, `role_deleted` |
| `name` | VARCHAR(80) | NOT NULL | Nombre visible |
| `created_at`, `updated_at` | TIMESTAMP | | |

## admin_actions

| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK | |
| `user_id` | BIGINT UNSIGNED | FK → `users.id`, RESTRICT | Quien hizo la acción |
| `admin_action_type_id` | TINYINT UNSIGNED | FK → `admin_action_types.id`, RESTRICT | Qué hizo |
| `target_user_id` | BIGINT UNSIGNED | NULL, FK → `users.id`, RESTRICT | Persona afectada, si la hay |
| `target_role_id` | SMALLINT UNSIGNED | NULL, FK → `roles.id`, SET NULL | Rol afectado, si lo hay. Al eliminar un rol su historial queda sin enlace; el código y el nombre siguen en `details` |
| `details` | JSON | NULL | Lo que cambió, con sus valores anteriores y nuevos (p. ej. `{"role": {"from": "agent", "to": "supervisor"}}`) |
| `ip_address` | VARCHAR(45) | NULL | Desde dónde (alcanza para IPv6) |
| `user_agent` | VARCHAR(255) | NULL | Navegador |
| `created_at` | TIMESTAMP | NOT NULL | Sin `updated_at`: el registro no cambia nunca |

## Índices

| Índice | Uso |
|---|---|
| `(target_user_id, created_at)` | Qué le hicieron a una persona, en orden |
| `(user_id, created_at)` | Qué hizo cada administrador |
| `(admin_action_type_id, created_at)` | Acciones de un tipo (auditoría) |
| `target_role_id` | Cambios de un rol |
| `created_at` | Auditoría de administración sin filtros, de lo más reciente a lo más antiguo |

## Dónde está en el código

- Migraciones: [`000026`](../../../backend/database/migrations/2026_10_24_000026_create_admin_action_types_table.php), [`000027`](../../../backend/database/migrations/2026_10_24_000027_create_admin_actions_table.php) , [`000030` (SET NULL al eliminar un rol)](../../../backend/database/migrations/2026_10_24_000030_null_role_in_admin_actions_on_delete.php) y [`000032` (índice por fecha)](../../../backend/database/migrations/2026_10_24_000032_add_audit_indexes.php)
- Catálogo: [`AdminActionTypeSeeder.php`](../../../backend/database/seeders/AdminActionTypeSeeder.php)
- Escritura: [`DatabaseAdminLog.php`](../../../backend/app/Modules/Users/Infrastructure/DatabaseAdminLog.php)
- Explicación funcional: [Módulo Users](../../backend/users.md) y, para leerlo, [Módulo Audit](../../backend/audit.md)
