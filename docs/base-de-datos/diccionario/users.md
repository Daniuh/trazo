---
title: users
description: Usuarios del sistema.
audience: tecnico
area: base-de-datos
module: users
order: 10
---

# users

Personas que usan Trazo. **Se desactivan, nunca se borran**, para conservar el historial de lo que hicieron.

| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK | Clave interna; nunca sale por la API |
| `ulid` | CHAR(26) | UNIQUE, NOT NULL | Identificador público, inmutable |
| `given_names` | VARCHAR(80) | NOT NULL | Nombres, uno o varios ("María José") |
| `family_names` | VARCHAR(80) | NOT NULL | Apellidos, uno o varios ("De la Torre Gómez") |
| `name` | VARCHAR(161) | Calculada y almacenada, `TRIM(CONCAT(given_names, ' ', family_names))` | Nombre completo para mostrar. No se escribe ([ADR-0086](../../decisiones/0086-nombres-y-apellidos.md)) |
| `email` | VARCHAR(191) | UNIQUE, NOT NULL | Siempre en minúsculas y sin espacios |
| `password` | VARCHAR(255) | NOT NULL | Hash Argon2id |
| `is_active` | BOOLEAN | NOT NULL, por defecto `true` | Un usuario inactivo no puede entrar |
| `mfa_grace_used` | TINYINT UNSIGNED | NOT NULL, por defecto `0` | Ingresos en que pospuso configurar el MFA; se compara con la gracia de su política ([MFA](mfa.md)) |
| `last_login_at` | TIMESTAMP | NULL | Último ingreso completo (después del MFA). Null si nunca ha ingresado. Ayuda a detectar cuentas abandonadas |
| `created_at`, `updated_at` | TIMESTAMP | | |

Una cuenta creada desde la administración nace con una contraseña aleatoria que nadie conoce: la persona crea la suya con el flujo de recuperación ([Módulo Users](../../backend/users.md#cuenta-nueva-y-acceso-restablecido)).

## Índices

| Índice | Uso |
|---|---|
| `(is_active, family_names, given_names)` | Listas de personas activas ordenadas por apellido: selector de "asignar a", administración y auditoría |

## Referenciada por

`incidents.created_by`, `incidents.assigned_to`, `incident_activities.user_id`, `user_permissions.granted_by`, `admin_actions.user_id` y `admin_actions.target_user_id` (todas RESTRICT), y `user_role.user_id`, `user_permissions.user_id` y `personal_access_tokens.tokenable_id` (CASCADE).

## Dónde está en el código

- Migración: [`2026_11_01_000001_create_users_table.php`](../../../backend/database/migrations/2026_11_01_000001_create_users_table.php)
- Modelo: [`Users/Infrastructure/Models/User.php`](../../../backend/app/Modules/Users/Infrastructure/Models/User.php)
