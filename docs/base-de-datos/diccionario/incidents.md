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
| `code` | VARCHAR(20) | UNIQUE, NOT NULL | Código legible: prefijo, año y consecutivo del año (`TZ-26-0142`), tomado de [`incident_code_sequences`](incident-code-sequences.md) |
| `title` | VARCHAR(150) | NOT NULL | La API acepta de 5 a 80 caracteres ([ADR-0073](../../decisiones/0073-largo-del-titulo.md)); la columna conserva 150 porque las migraciones son solo aditivas |
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
| `(status_id, priority_id, deleted_at, created_by, assigned_to)` | Filtros combinados y conteo del dashboard por estado y prioridad, sin leer la tabla (también para quien solo ve lo suyo) |
| `(status_id, due_date, deleted_at)` | Conteo de vencidos, resuelto solo con el índice |
| `(priority_id)` | Filtro por prioridad (y su FK) |
| `(assigned_to, status_id, due_date, deleted_at, created_by)` | Filtro por asignado, "asignados a mí", "sin asignar" y carga por agente, sin leer la tabla |
| `(created_by)` | "Creados por mí" (y su FK) |
| `(due_date, deleted_at)` | Rango y orden por vencimiento; conteo general solo con el índice |
| `(created_at, deleted_at)` | Rango y orden por creación (orden por defecto); creados por día |
| `(resolved_at, deleted_at)` | Resueltos por día (tendencia del dashboard) |
| `FULLTEXT (title, description)` | Búsqueda por texto (palabras de 3 o más letras) |

Los índices están validados con `EXPLAIN` sobre 100.000 registros: ver [Rendimiento](../rendimiento.md).

## Dónde está en el código

- Migración: [`2026_10_21_000010_create_incidents_table.php`](../../../backend/database/migrations/2026_10_21_000010_create_incidents_table.php)
- Modelo: [`Incidents/Infrastructure/Models/Incident.php`](../../../backend/app/Modules/Incidents/Infrastructure/Models/Incident.php)
- Decisiones: [ADR-0012](../../decisiones/0012-bloqueo-optimista.md) y [ADR-0013](../../decisiones/0013-busqueda-fulltext.md)
