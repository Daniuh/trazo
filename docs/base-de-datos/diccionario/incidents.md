---
title: incidents
description: Solicitudes e incidentes, núcleo del sistema.
audience: tecnico
area: base-de-datos
module: incidents
order: 13
---

# incidents

Cada solicitud o incidente reportado. Contiene todos los campos que exige la prueba (id, título, descripción, prioridad, estado, usuario creador, usuario asignado, fecha de vencimiento y timestamps) más las mejoras aprobadas.

| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK | Clave interna |
| `ulid` | CHAR(26) | UNIQUE, NOT NULL | Identificador público para la API |
| `code` | VARCHAR(20) | UNIQUE, NOT NULL | Código legible: `INC-000123` |
| `title` | VARCHAR(150) | NOT NULL | |
| `description` | TEXT | NOT NULL | |
| `priority_id` | TINYINT UNSIGNED | FK → `priorities`, RESTRICT | |
| `status_id` | TINYINT UNSIGNED | FK → `statuses`, RESTRICT | |
| `created_by` | BIGINT UNSIGNED | FK → `users`, RESTRICT, NOT NULL | Quién lo reportó; inmutable |
| `assigned_to` | BIGINT UNSIGNED | FK → `users`, RESTRICT, NULL | Quién lo atiende; vacío mientras está sin asignar |
| `due_date` | DATETIME | NOT NULL | Fecha de vencimiento (sugerida según la prioridad, editable) |
| `resolved_at` | TIMESTAMP | NULL | Habilita métricas de tiempo de resolución |
| `lock_version` | INT UNSIGNED | NOT NULL, CHECK ≥ 1 | Bloqueo optimista |
| `created_at`, `updated_at` | TIMESTAMP | | |
| `deleted_at` | TIMESTAMP | NULL | Borrado lógico |

No hay `updated_by`: quién modificó qué ya queda en `incident_activities`, y guardarlo aquí repetiría información.

## Índices

| Índice | Consulta que atiende |
|---|---|
| `(status_id, priority_id)` | Filtros combinados y conteos del dashboard |
| `(status_id, due_date)` | Conteo de vencidos |
| `(priority_id)` | Filtro por prioridad (y su FK) |
| `(assigned_to, status_id)` | Filtro por asignado y "mis incidentes" |
| `(created_by)` | "Creados por mí" (y su FK) |
| `(due_date)` | Rango y orden por vencimiento |
| `(created_at)` | Rango y orden por creación, orden por defecto |
| `FULLTEXT (title, description)` | Búsqueda por texto (palabras de 3 o más letras) |

Los índices se validan con `EXPLAIN` sobre más de 100.000 registros al construir el listado.

## Dónde está en el código

- Migración: [`2026_10_21_000010_create_incidents_table.php`](../../../backend/database/migrations/2026_10_21_000010_create_incidents_table.php)
- Modelo: [`Incidents/Infrastructure/Models/Incident.php`](../../../backend/app/Modules/Incidents/Infrastructure/Models/Incident.php)
- Decisiones: [ADR-0012](../../decisiones/0012-bloqueo-optimista.md) y [ADR-0013](../../decisiones/0013-busqueda-fulltext.md)
