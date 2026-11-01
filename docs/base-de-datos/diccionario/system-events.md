---
title: system_events
description: Eventos del sistema agrupados por huella (fallas, infraestructura y seguridad) y su catálogo de tipos.
audience: tecnico
area: base-de-datos
module: system
order: 20
---

# system_events

Eventos relevantes del sistema: fallas que nadie ve, piezas que dejan de responder y alertas de seguridad. Lo que se repite no crea filas nuevas: suma al contador de la misma fila, identificada por su huella ([ADR-0022](../../decisiones/0022-logs-relevantes.md) y [ADR-0059](../../decisiones/0059-sistema-en-la-practica.md)).

## system_event_types

| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | TINYINT UNSIGNED | PK | |
| `code` | VARCHAR(40) | UNIQUE, NOT NULL | `unhandled_error`, `job_failed`, `queue_backlog`, `service_down`, `external_service_unavailable`, `login_locked`, `mfa_attempts_exceeded`, `reset_code_voided`, `reauthentication_locked`, `access_denied_repeated` |
| `name` | VARCHAR(80) | NOT NULL | Nombre para la pantalla |
| `category` | VARCHAR(20) | NOT NULL, CHECK | `failure`, `infrastructure` o `security` |
| `severity` | VARCHAR(10) | NOT NULL, CHECK | `info`, `warning` o `critical` |
| `created_at`, `updated_at` | TIMESTAMP | | |

La severidad vive en el tipo: todos los eventos de un tipo pesan lo mismo, y cambiarla es editar un dato, no el código.

## system_events

| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK | |
| `system_event_type_id` | TINYINT UNSIGNED | FK → `system_event_types.id`, RESTRICT | Qué pasó |
| `fingerprint` | CHAR(64) | UNIQUE, NOT NULL | SHA-256 del tipo y su origen (la clase y la línea de un error, un servicio, una cuenta) |
| `message` | VARCHAR(255) | NOT NULL | Texto corto, sin datos sensibles; se actualiza con la última vez |
| `context` | JSON | NULL | Datos para entenderlo (ruta, servicio, cuenta enmascarada…). Nunca tokens, contraseñas ni contenido de jobs |
| `occurrences` | INT UNSIGNED | NOT NULL, 1 | Cuántas veces pasó |
| `first_seen_at`, `last_seen_at` | TIMESTAMP | NOT NULL | Primera y última vez |
| `trace_id` | VARCHAR(64) | NULL | Identificador de la última petición que lo produjo, para cruzarlo con los logs (vacío en la cola y el scheduler) |
| `status` | VARCHAR(15) | NOT NULL, CHECK, `open` | `open`, `acknowledged` o `resolved`. Un resuelto que vuelve a pasar se reabre |
| `status_changed_by` | BIGINT UNSIGNED | NULL, FK → `users.id`, RESTRICT | Quién cambió el estado por última vez |
| `status_changed_at` | TIMESTAMP | NULL | Cuándo |

Los resueltos se borran a los 90 días de resolverse (`system:prune-events`, configurable con `SYSTEM_EVENTS_RETENTION_DAYS`).

## Índices

| Índice | Uso |
|---|---|
| `fingerprint` (único) | Agrupar en una sola sentencia (`INSERT … ON DUPLICATE KEY UPDATE`) |
| `(status, last_seen_at)` | La lista por defecto: pendientes, de lo más reciente a lo más antiguo |
| `(system_event_type_id, last_seen_at)` | Filtro por tipo |
| `last_seen_at` | Lista sin filtros |
| `status_changed_by` | Clave foránea |

## Dónde está en el código

- Migraciones: [`000033`](../../../backend/database/migrations/2026_10_25_000033_create_system_event_types_table.php) y [`000034`](../../../backend/database/migrations/2026_10_25_000034_create_system_events_table.php)
- Catálogo: [`SystemEventTypeSeeder.php`](../../../backend/database/seeders/SystemEventTypeSeeder.php)
- Escritura: [`DatabaseSystemEvents.php`](../../../backend/app/Modules/System/Infrastructure/DatabaseSystemEvents.php); lectura: [`DatabaseEventLog.php`](../../../backend/app/Modules/System/Infrastructure/DatabaseEventLog.php)
- Explicación funcional: [Módulo System](../../backend/system.md)
