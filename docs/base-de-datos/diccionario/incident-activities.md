---
title: incident_activities
description: Historial inmutable de cada incidente.
audience: tecnico
area: base-de-datos
module: incidents
order: 14
---

# incident_activities

Cada cambio de un incidente queda registrado aquí: quién, qué y cuándo. Es la base del historial del detalle y de la auditoría global. **Es de solo escritura:** el modelo impide editar o borrar un registro y las claves foráneas impiden borrar lo que referencia.

| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK | |
| `incident_id` | BIGINT UNSIGNED | FK → `incidents`, RESTRICT | |
| `user_id` | BIGINT UNSIGNED | FK → `users`, RESTRICT | Quién hizo el cambio |
| `activity_type_id` | TINYINT UNSIGNED | FK → `activity_types`, RESTRICT | |
| `changes` | JSON | NULL | Valores anteriores y nuevos |
| `ip_address` | VARCHAR(45) | NULL | Alcanza para IPv6. Vacía en el historial de los datos de demostración, que no viene de una petición |
| `user_agent` | VARCHAR(255) | NULL | |
| `created_at` | TIMESTAMP | NOT NULL | Sin `updated_at`: el registro no cambia |

## Índices

| Índice | Consulta que atiende |
|---|---|
| `(incident_id, created_at)` | Historial del detalle, en orden |
| `(user_id, created_at)` | Actividad por usuario en la auditoría global |
| `(activity_type_id)` | Filtro por tipo (y su FK) |
| `(created_at)` | Auditoría global sin filtros, de lo más reciente a lo más antiguo; InnoDB agrega el id, que es el desempate del cursor |
| `(activity_type_id, created_at)` | Auditoría global filtrada por un tipo |

Esta es la tabla que más crece. Su estrategia de crecimiento (archivado en lugar de particionamiento, para conservar las FK) está en [ADR-0014](../../decisiones/0014-crecimiento.md).

## Dónde está en el código

- Migración: [`2026_10_21_000011_create_incident_activities_table.php`](../../../backend/database/migrations/2026_10_21_000011_create_incident_activities_table.php)
- Índices de la auditoría: [`000032`](../../../backend/database/migrations/2026_10_24_000032_add_audit_indexes.php) ([ADR-0056](../../decisiones/0056-auditoria-global.md))
- Modelo: [`IncidentActivity.php`](../../../backend/app/Modules/Incidents/Infrastructure/Models/IncidentActivity.php)
