---
title: Módulo Audit
description: Auditoría global de solo lectura sobre el historial de incidentes y el registro de administración, con permisos separados, alcance por jerarquía y paginación por cursor.
audience: tecnico
area: backend
module: audit
order: 7
---

# Módulo Audit

Muestra lo que ya guardan otros módulos: el historial de todos los incidentes (`incident_activities`) y el registro de la administración (`admin_actions`). **No escribe nada:** solo lee, filtra y decide quién ve qué. Cada registro trae la IP y el navegador desde donde se hizo, que el historial de un incidente no muestra.

## Endpoints

Todos exigen sesión.

| Método | Ruta | Permiso | Respuesta |
|---|---|---|---|
| `GET` | `/api/v1/audit/catalog` | `audit.incidents.view` o `audit.admin.view` | Tipos de cada pestaña que se puede ver (la otra va en `null`), roles y permisos (solo con `audit.admin.view`) y personas |
| `GET` | `/api/v1/audit/incidents` | `audit.incidents.view` | Historial de todos los incidentes, también de los eliminados |
| `GET` | `/api/v1/audit/admin` | `audit.admin.view` | Registro de administración dentro del alcance de quien consulta |

Parámetros de las dos listas:

| Parámetro | Qué hace |
|---|---|
| `filter[from]`, `filter[to]` | Rango (ISO 8601). Sin fechas, los últimos 30 días. Con solo `to`, los 30 días anteriores. Con solo `from`, hasta ahora |
| `filter[actor]` | ULID de quien hizo el cambio |
| `filter[type]` | Códigos de tipo, separados por comas |
| `filter[incident]` | Solo incidentes: el código como lo escriba una persona (`INC-123`, `inc-000123` o `123`) |
| `filter[user]`, `filter[role]` | Solo administración: persona afectada (ULID) o rol afectado (código; también encuentra un rol eliminado) |
| `cursor` | El `meta.next_cursor` de la respuesta anterior |
| `per_page` | De 1 a 100; 25 por defecto |

Un filtro que apunta a algo que no existe (una persona, un tipo o un incidente) devuelve la lista vacía.

```json
GET /api/v1/audit/incidents?filter[incident]=INC-629

{
  "data": [{
    "id": 3159,
    "created_at": "2026-10-24T21:54:57+00:00",
    "type": { "code": "status_changed", "name": "Cambio de estado" },
    "user": { "id": "01m5nzqh9rk9w9sr18d87hwkat", "name": "Carlos Mejía" },
    "incident": { "id": "01m5qyhqrkxexrgj35w0b3fh9y", "code": "INC-000629", "title": "…", "deleted": false },
    "changes": { "status": { "from": { "code": "open", "name": "Abierto" }, "to": { "code": "in_progress", "name": "En progreso" } } },
    "ip_address": "172.20.0.1",
    "user_agent": "Mozilla/5.0 …",
    "links": { "incident": true }
  }],
  "meta": { "next_cursor": "MjAyNi0xMC0yNCAyMTo1NDo1NHwzMTU4", "from": "2026-09-24T…", "to": "2026-10-24T…" }
}
```

En la administración, cada registro trae `target_user` y `target_role` en lugar de `incident`, y `details` en lugar de `changes`. Un rol eliminado llega con `deleted: true` y con el código y el nombre que quedaron en el detalle.

## Quién ve qué

| Rol | Incidentes | Administración |
|---|---|---|
| Supervisor | Todo | — |
| Administrador | — | Lo propio y lo que toca a personas o roles por debajo de su nivel |
| Super admin | Todo | Todo |

- **Dos permisos.** Con un solo `audit.view`, el Administrador veía los incidentes de todos y el Supervisor veía la administración. Los dos se pueden conceder a una persona puntual.
- **Alcance de la administración** (`AdminAuditScope`). Para quien no es la cima, un registro se ve si lo hizo esa persona, o si la persona o el rol afectados están por debajo de su nivel.
  - El nivel de una persona es el de su rol más alto **hoy**.
  - Un rol eliminado se mide por el nivel guardado en el detalle.
  - Un registro sin persona ni rol afectado solo lo ve quien lo hizo y la cima. Es el caso de las [exportaciones de incidentes](incidents.md#exportación) (`incidents_exported`), que guardan el número de filas y los filtros.
  - La misma regla está en SQL (`DatabaseAdminAudit`) y una prueba compara las dos registro por registro.
- **Enlaces** (`links`): el back dice si quien consulta puede abrir lo que se nombra.
  - El incidente, con la misma regla del detalle (`IncidentAccess`).
  - La ficha de la persona, con `users.view`.
  - La del rol, con `roles.manage` y si el rol todavía existe.

## Cursor

- **Orden.** De lo más reciente a lo más antiguo, por (`created_at`, `id`). El id desempata los registros del mismo segundo.
- **Cursor.** Es la fecha y el id del último registro entregado, en base64 sin relleno: opaco para el front. Uno alterado responde `422` con `INVALID_CURSOR`.
- **Fin de la lista.** Se pide una fila de más: si llega, hay otra tanda y `next_cursor` trae su cursor; si no, va en `null`.
- **Sin total.** No se cuenta la tabla. Contar recorrería todo el rango, cada vez más grande.

Rendimiento medido sobre 523.088 filas: menos de 2 ms en cada caso, sin ordenar en memoria ([ADR-0056](../decisiones/0056-auditoria-global.md)).

## Errores

| Código | HTTP | Cuándo |
|---|---|---|
| `FORBIDDEN_ACTION` | 403 | Sin el permiso de la pestaña |
| `INVALID_CURSOR` | 422 | El cursor no es uno que entregó la API |
| `INCIDENT_CODE_FORMAT` | 422 | `filter[incident]` no tiene forma de código |
| `DATE_TOO_EARLY` | 422 | `filter[to]` es anterior a `filter[from]` |

## Capas

| Capa | Pieza | Qué hace |
|---|---|---|
| Domain | `AuditAccess` | Permisos de cada pestaña y alcance de la administración |
| Domain | `AdminAuditScope`, `AuditPeriod`, `AuditCursor` | Regla "solo hacia abajo"; rango por defecto; cursor que va y vuelve |
| Application | `ListIncidentAudit`, `ListAdminAudit`, `ReadAuditCatalog` | Casos de uso: revisan el permiso y piden la consulta |
| Application | `Ports/*`, `Data/*` | Contratos de las consultas y los datos que devuelven |
| Infrastructure | `DatabaseIncidentAudit`, `DatabaseAdminAudit` | Consultas en SQL. Traducen ULID, tipos e incidente a id antes de consultar, para usar el índice que corresponde |
| Infrastructure | `AuditWindow`, `DatabaseAuditCatalog` | Rango, cursor y fila de más, comunes a las dos; catálogos |
| Http | `AuditController`, `AuditResource` | Respuesta con `meta` y `links` |
| Http | `Requests/AuditRequest` (+ `IncidentAuditRequest`, `AdminAuditRequest`) | Revisan el permiso antes de validar, igual que la administración (`ChecksPermission`) |
| Http | `Rules/IncidentCode`, `Rules/Cursor` | Código de incidente normalizado a `INC-000123` y cursor válido |

Quien consulta se arma con `RequestAdmin` del módulo Users: nivel y permisos efectivos.

## Dónde está en el código

- [`backend/app/Modules/Audit/`](../../backend/app/Modules/Audit/)
- Rutas: [`Http/routes.php`](../../backend/app/Modules/Audit/Http/routes.php)
- Migraciones: [`000031`](../../backend/database/migrations/2026_10_24_000031_split_audit_view_permission.php) y [`000032`](../../backend/database/migrations/2026_10_24_000032_add_audit_indexes.php)
- Pruebas: [`tests/Feature/Audit/AuditTest.php`](../../backend/tests/Feature/Audit/AuditTest.php) y [`tests/Unit/Audit/AuditDomainTest.php`](../../backend/tests/Unit/Audit/AuditDomainTest.php)
- Decisión: [ADR-0056](../decisiones/0056-auditoria-global.md)
