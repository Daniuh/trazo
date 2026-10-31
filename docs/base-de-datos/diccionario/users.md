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
| `name` | VARCHAR(120) | NOT NULL | Nombre visible |
| `email` | VARCHAR(191) | UNIQUE, NOT NULL | Siempre en minúsculas y sin espacios |
| `password` | VARCHAR(255) | NOT NULL | Hash Argon2id |
| `is_active` | BOOLEAN | NOT NULL, por defecto `true` | Un usuario inactivo no puede entrar |
| `created_at`, `updated_at` | TIMESTAMP | | |

## Índices

| Índice | Uso |
|---|---|
| `(is_active, name)` | Selector de "asignar a": solo activos, ordenados por nombre |

## Referenciada por

`incidents.created_by`, `incidents.assigned_to`, `incident_activities.user_id` (todas RESTRICT) y `user_role.user_id` y `personal_access_tokens.tokenable_id` (CASCADE).

## Dónde está en el código

- Migración: [`2026_10_21_000001_create_users_table.php`](../../../backend/database/migrations/2026_10_21_000001_create_users_table.php)
- Modelo: [`Users/Infrastructure/Models/User.php`](../../../backend/app/Modules/Users/Infrastructure/Models/User.php)
