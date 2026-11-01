---
title: incident_reopen_requests
description: Solicitudes para reabrir un incidente cerrado o cancelado, con su decisión.
audience: tecnico
area: base-de-datos
module: incidents
order: 15
---

# incident_reopen_requests

Cuando alguien pide reabrir un incidente cerrado o cancelado, la solicitud queda aquí hasta que quien supervisa la aprueba o la rechaza. Las filas no se borran: son parte del rastro del incidente. Reglas y alternativas en el [ADR-0071](../../decisiones/0071-nota-de-estado-y-reapertura.md).

| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK | |
| `incident_id` | BIGINT UNSIGNED | FK → `incidents`, RESTRICT | |
| `requested_by` | BIGINT UNSIGNED | FK → `users`, RESTRICT | Quién la pidió |
| `reason` | VARCHAR(1000) | NOT NULL | Por qué hay que reabrirlo |
| `status` | VARCHAR(10) | NOT NULL, CHECK `IN ('pending', 'approved', 'rejected')` | Pendiente por defecto |
| `decided_by` | BIGINT UNSIGNED | NULL, FK → `users`, RESTRICT | Quién la decidió |
| `decision_note` | VARCHAR(1000) | NULL | Motivo del rechazo |
| `decided_at` | TIMESTAMP | NULL | Cuándo se decidió |
| `created_at`, `updated_at` | TIMESTAMP | | |
| `pending_incident_id` | BIGINT UNSIGNED | Columna calculada (STORED), UNIQUE | Igual a `incident_id` mientras está pendiente; NULL después |

- **Una sola pendiente por incidente:** el índice único sobre `pending_incident_id` lo garantiza en la base, aunque dos personas la pidan a la vez. MySQL admite varios NULL en un índice único, así que las decididas no chocan entre sí.
- **CHECK de la decisión:** una pendiente no tiene `decided_by` ni `decided_at`, y una decidida tiene los dos.
- **RESTRICT en las FK:** ni el incidente (que usa borrado lógico) ni las personas (que se desactivan) se borran mientras tengan solicitudes.
- El motivo de cada paso también queda como nota en [incident_activities](incident-activities.md) (`reopen_requested`, `reopen_rejected`, `reopened`), que es lo que muestran el historial y la auditoría.

## Índices

| Índice | Consulta que atiende |
|---|---|
| `UNIQUE (pending_incident_id)` | La solicitud pendiente de un incidente (detalle) y el filtro "Con reapertura pendiente" del listado |
| `(incident_id, created_at)` | Solicitudes de un incidente, en orden (y su FK) |
| `(requested_by)`, `(decided_by)` | Sus FK |

## Dónde está en el código

- Migración: [`2026_10_27_000037_create_incident_reopen_requests_table.php`](../../../backend/database/migrations/2026_10_27_000037_create_incident_reopen_requests_table.php)
- Modelo: [`IncidentReopenRequest.php`](../../../backend/app/Modules/Incidents/Infrastructure/Models/IncidentReopenRequest.php)
- Escritura: [`EloquentReopenRequests.php`](../../../backend/app/Modules/Incidents/Infrastructure/EloquentReopenRequests.php)
