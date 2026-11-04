---
title: notifications
description: Avisos personales del centro de notificaciones y su catálogo de tipos.
audience: tecnico
area: base-de-datos
module: notifications
order: 24
---

# notifications

Avisos de cada persona sobre lo que pasa en sus incidentes ([ADR-0077](../../decisiones/0077-centro-de-notificaciones.md)). No copian textos: apuntan al mensaje o al registro del historial que los originó, y de ahí salen quién lo hizo y qué cambió.

## notification_types

| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | TINYINT UNSIGNED | PK | |
| `code` | VARCHAR(40) | UNIQUE, NOT NULL | `comment_received`, `incident_assigned`, `status_changed`, `incident_reopened`, `reopen_rejected` y los de coordinación `unassigned_created`, `critical_created`, `reopen_requested`, `incident_overdue` ([ADR-0085](../../decisiones/0085-avisos-de-coordinacion.md)) |
| `name` | VARCHAR(80) | NOT NULL | Nombre visible |
| `category` | VARCHAR(20) | NOT NULL, CHECK `IN ('conversations', 'activity')` | Pestaña del centro de notificaciones |
| `created_at`, `updated_at` | TIMESTAMP | | |

## notifications

| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK | Clave interna; también ordena la lista |
| `ulid` | CHAR(26) | UNIQUE, NOT NULL | Identificador público (API) |
| `user_id` | BIGINT UNSIGNED | FK → `users`, NOT NULL | A quién va el aviso |
| `notification_type_id` | TINYINT UNSIGNED | FK → `notification_types`, NOT NULL | Qué tipo de aviso es |
| `incident_id` | BIGINT UNSIGNED | FK → `incidents`, NOT NULL | Incidente del aviso |
| `comment_id` | BIGINT UNSIGNED | FK → `incident_comments`, NULL | El último mensaje, en los avisos de conversación |
| `activity_id` | BIGINT UNSIGNED | FK → `incident_activities`, NULL | El registro del historial, en los avisos de actividad |
| `occurrences` | SMALLINT UNSIGNED | NOT NULL, por defecto 1 | Mensajes agrupados en el aviso |
| `read_at` | TIMESTAMP | NULL | Cuándo lo leyó |
| `created_at` | TIMESTAMP | NOT NULL | Cuándo se creó (UTC) |
| `pending_comment_incident_id` | BIGINT UNSIGNED | Calculada (STORED) | `incident_id` mientras es un aviso de mensajes sin leer; `NULL` después |

## Restricciones

| Restricción | Regla |
|---|---|
| `chk_notifications_source` | `comment_id IS NULL OR activity_id IS NULL`: un aviso sale de un mensaje o de un registro del historial, no de los dos |
| UNIQUE `(user_id, pending_comment_incident_id)` | Un solo aviso de mensajes sin leer por persona e incidente (el índice único admite varios `NULL`) |
| FK `user_id` | `CASCADE`: los avisos son de la persona y no tienen sentido sin ella |
| FK `activity_id` | `CASCADE`: un aviso no tiene sentido sin el registro que lo originó (por ejemplo, si el historial se archiva) |
| FK `incident_id`, `comment_id` | `RESTRICT`: incidentes y mensajes no se borran de verdad (borrado lógico), y MySQL no admite `CASCADE` en columnas de las que depende una columna calculada |
| FK `notification_type_id` | `RESTRICT`: un tipo en uso no se borra |

## Índices

| Índice | Uso |
|---|---|
| `ulid` (único) | Marcar uno como leído y pedir los anteriores a uno (`before`) |
| `(user_id, id)` | La lista de una persona, de la más nueva a la más vieja, filtrada por los ids de los tipos de la pestaña |
| `(user_id, read_at)` | Contar las sin leer de cada pestaña y marcarlas como leídas |
| `(user_id, pending_comment_incident_id)` (único) | Encontrar el aviso de mensajes sin leer de un incidente para sumarle uno |
| `created_at` | La limpieza diaria |

Validados con `EXPLAIN ANALYZE` sobre 60.000 avisos: la página de 20 tarda 0,8 ms y las sin leer menos de 0,1 ms.

## Sin `updated_at`

Lo único que cambia es la lectura, que tiene su propia columna. Un aviso de mensajes que suma uno se reemplaza por uno nuevo (para que suba al primer lugar), no se actualiza.

## Retención

Se borran a los 90 días de creados, leídos o no (`notifications:prune`, configurable con `NOTIFICATIONS_RETENTION_DAYS`).

## Dónde está en el código

- Migraciones: [`000041`](../../../backend/database/migrations/2026_11_01_000041_create_notification_types_table.php) y [`000042`](../../../backend/database/migrations/2026_11_01_000042_create_notifications_table.php)
- Catálogo: [`NotificationTypeSeeder`](../../../backend/database/seeders/NotificationTypeSeeder.php)
- Acceso: [`DatabaseNotificationStore`](../../../backend/app/Modules/Notifications/Infrastructure/DatabaseNotificationStore.php)
- Explicación funcional: [Módulo Notifications](../../backend/notifications.md)
