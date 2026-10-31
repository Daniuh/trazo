---
title: Módulo Incidents
description: Crear, ver, editar, cambiar de estado, asignar, eliminar y restaurar incidentes, con sus reglas de acceso.
audience: tecnico
area: backend
module: incidents
order: 4
---

# Módulo Incidents

Gestiona el ciclo de vida de los incidentes. **Todas las reglas viven aquí:** quién puede hacer qué, en qué estado, con qué transiciones y qué se registra. El front solo muestra lo que la API le dice que está permitido.

## Endpoints

| Método | Ruta | Para qué | Permiso |
|---|---|---|---|
| `GET` | `/catalogs` | Prioridades y estados con sus transiciones (con `ETag`) | Sesión |
| `GET` | `/incidents/assignees` | Personas a las que se puede asignar: cuentas activas con el permiso `incidents.status.assigned`, por su rol o concedido a la persona ([permisos por persona](users.md#permisos-por-persona)) | `incidents.assign` |
| `GET` | `/incidents` | Listado paginado, con orden, búsqueda y filtros (ver abajo) | Sesión; cada persona ve lo suyo |
| `POST` | `/incidents` | Crear (acepta `Idempotency-Key`) | `incidents.create` |
| `GET` | `/incidents/{id}` | Detalle con `actions` y `next_statuses` | Verlo |
| `PUT` | `/incidents/{id}` | Editar título, descripción, prioridad y vencimiento | Ver la tabla de reglas |
| `PUT` | `/incidents/{id}/status` | Cambiar el estado | Ver la tabla de reglas |
| `PUT` | `/incidents/{id}/assignee` | Tomar, asignar o quitar la asignación (`null`) | Ver la tabla de reglas |
| `DELETE` | `/incidents/{id}` | Borrado lógico | `incidents.delete` |
| `POST` | `/incidents/{id}/restore` | Restaurar | `incidents.restore` |
| `GET` | `/incidents/{id}/activities` | Historial, de lo más reciente a lo más antiguo, 50 por página | Verlo |

Todas las rutas van bajo `/api/v1` y exigen sesión. `{id}` es el ULID del incidente. Las escrituras sobre un incidente existente exigen `version`, la que se conocía al abrirlo.

### Respuesta del detalle

```json
{
  "data": {
    "id": "01m5j1sf78wr8p15zs84s3gdaq",
    "code": "INC-000001",
    "title": "No carga facturación",
    "description": "…",
    "priority": { "code": "high", "name": "Alta", "level": 3 },
    "status": { "code": "in_progress", "name": "En progreso", "is_final": false },
    "creator": { "id": "01m5…", "name": "Juan Pérez" },
    "assignee": { "id": "01m5…", "name": "Laura Gómez" },
    "due_date": "2026-10-23T14:56:07+00:00",
    "is_overdue": false,
    "resolved_at": null,
    "created_at": "…", "updated_at": "…", "deleted_at": null,
    "version": 3,
    "actions": { "update": true, "change_status": true, "take": false, "assign": true, "delete": true, "restore": false },
    "next_statuses": [{ "code": "on_hold", "name": "En espera" }, { "code": "resolved", "name": "Resuelto" }]
  }
}
```

- **`actions`** indica qué puede hacer esta persona con este incidente, ahora. El front muestra u oculta botones solo con esto.
- **`next_statuses`** son los estados a los que esta persona puede llevarlo. Si no puede cambiar el estado, viene vacío.
- **`is_overdue`** se calcula en el back: el estado es pendiente y el vencimiento ya pasó.

## Listado

```
GET /api/v1/incidents?page=2&per_page=20&sort=-due_date&search=impresora
    &filter[status]=open,in_progress&filter[priority]=high,critical
    &filter[assignee]=none,01m5…&filter[creator]=01m5…
    &filter[created_from]=2026-10-01T00:00:00Z&filter[due_to]=2026-10-31T23:59:59Z
    &filter[overdue]=1&filter[deleted]=1
```

| Parámetro | Detalle |
|---|---|
| `page`, `per_page` | Página (desde 1) y tamaño: 20 por defecto, máximo 100 |
| `sort` | `created_at` (por defecto, descendente), `due_date`, `updated_at`, `priority`, `status` o `code`. Con `-` delante, descendente. Siempre se desempata por id |
| `search` | Al menos una palabra de 3 letras; cada palabra es obligatoria y se busca por prefijo en el título y la descripción. Un código (`INC-123`) se busca exacto |
| `filter[status]`, `filter[priority]` | Códigos del catálogo, separados por comas |
| `filter[assignee]` | ULIDs separados por comas; `none` agrega "sin asignar" |
| `filter[creator]` | ULID de quien lo reportó |
| `filter[created_from]`, `filter[created_to]`, `filter[due_from]`, `filter[due_to]` | Fechas ISO 8601 (inclusive) |
| `filter[overdue]` | `1`: solo pendientes con el vencimiento pasado |
| `filter[deleted]` | `1`: solo eliminados. Requiere `incidents.restore` (si no, 403) |

- **Respuesta:** `data` trae una versión liviana de cada incidente (sin descripción ni estados siguientes, pero con `actions` e `is_overdue`). `meta` trae `page`, `per_page`, `total` y `last_page`.
- **Errores de validación:** `OUT_OF_RANGE` (tamaño de página), `INVALID_OPTION` (orden), `SEARCH_TOO_SHORT` (búsqueda) e `INVALID_REFERENCE` (código de catálogo inexistente).
- **Visibilidad:** la misma regla del detalle (`VisibilityScope`), aplicada en SQL; una prueba compara ambos resultados para cada persona. Ver [ADR-0044](../decisiones/0044-listado-de-incidentes.md).
- **Rendimiento:** validado con `EXPLAIN` sobre 100.000 incidentes. La mayoría de los escenarios responde en menos de 100 ms; los dos que pueden superarlo con la máquina cargada están anotados en [Rendimiento](../base-de-datos/rendimiento.md).

## Reglas de acceso

| Acción | Quién | Además |
|---|---|---|
| Ver | `view.any` todos; `view.own` los que creó; `view.assigned` los asignados a él; `view.unassigned` los que nadie tiene | Si no puede verlo, la API responde **404**, como si no existiera |
| Editar | `update.any` cualquiera; `update.own` los que creó, **solo mientras están en el estado inicial (Abierto)** | Nunca en un estado final (Cerrado o Cancelado) |
| Cambiar estado | `status.any` cualquiera; `status.assigned` los asignados a él | Solo por las transiciones del catálogo; nunca desde un estado final |
| Tomar | `incidents.take` | Solo si nadie lo tiene |
| Asignar o quitar | `incidents.assign` | Solo a personas activas que atienden incidentes; nunca en un estado final |
| Eliminar / restaurar | `incidents.delete` / `incidents.restore` | Un eliminado solo lo ve quien puede restaurarlo |

Las reglas consultan **permisos**, nunca nombres de rol. Están en una sola clase de dominio, `IncidentAccess`, que usan los casos de uso y el campo `actions`. Así, lo que el front muestra y lo que la API permite nunca difieren.

**Asignar al crear:** el campo `assignee` de `POST /incidents` solo lo puede usar quien tiene `incidents.assign`. Al editar, la asignación se cambia con su propio endpoint.

## Reglas de negocio

- **Estado inicial:** todo incidente nace en el estado marcado como inicial en el catálogo (Abierto).
- **Vencimiento:** si quien crea no lo indica, se calcula con el tiempo de resolución de la prioridad (Baja 5 días, Media 3, Alta 1 y Crítica 4 horas). Un vencimiento nuevo no puede quedar en el pasado. Al editar, solo se envía si cambia, para que un incidente ya vencido se pueda seguir editando.
- **Fecha de resolución** (`resolved_at`), decidida por los datos del catálogo y no por códigos fijos:
  - al volver a un estado pendiente se borra;
  - al llegar a uno no pendiente y no final (Resuelto), se marca;
  - al cerrar o cancelar, se conserva.
- **Código legible:** `INC-000123` se forma con el id interno dentro de la misma transacción que crea el incidente. Nunca se repite ni se reutiliza.
- **Bloqueo optimista:** cada escritura comprueba la versión en el mismo `UPDATE … WHERE lock_version = ?`. Si otra persona guardó entretanto, responde 409 `VERSION_CONFLICT` y no se pierde ningún cambio.
- **Solo lo que cambió:** editar sin cambios no sube la versión ni deja registro.

## Historial

Cada escritura deja un registro en `incident_activities` dentro de la misma transacción: si el registro falla, el cambio tampoco se guarda.

| Tipo | Cuándo | `changes` |
|---|---|---|
| `created` | Al crear | La asignación inicial, si la hubo |
| `updated` | Al editar | Cada campo cambiado: `{ "from": …, "to": … }` |
| `status_changed` | Al cambiar de estado | Estado anterior y nuevo, con código y nombre |
| `assigned` / `unassigned` | Al asignar o quitar | Persona anterior y nueva, con id y nombre |
| `deleted` / `restored` | Al eliminar o restaurar | — |

Los nombres se guardan tal como estaban en ese momento, para que el historial se lea igual aunque alguien cambie de nombre después. La IP y el navegador se guardan, pero el historial del detalle no los muestra: quedan para la auditoría global.

## Errores

| Código | Estado | Cuándo |
|---|---|---|
| `RESOURCE_NOT_FOUND` | 404 | No existe o no puede verlo |
| `FORBIDDEN_ACTION` | 403 | Lo ve, pero su rol no incluye esa acción |
| `INCIDENT_FINALIZED` | 409 | Está cerrado o cancelado |
| `INCIDENT_EDIT_LOCKED` | 409 | Quien lo creó intenta editarlo cuando ya lo están atendiendo |
| `INCIDENT_ALREADY_ASSIGNED` | 409 | Intenta tomar uno que ya tiene alguien |
| `INCIDENT_NOT_DELETED` | 409 | Intenta restaurar uno que no está eliminado |
| `INVALID_STATUS_TRANSITION` | 409 | El catálogo no permite ese cambio (`params.from`, `params.to`) |
| `VERSION_CONFLICT` | 409 | Otra persona guardó un cambio desde que lo abrió |
| `INVALID_ASSIGNEE` | 422 | La persona no existe, está inactiva o no atiende incidentes |
| `IDEMPOTENCY_KEY_REUSED`, `IDEMPOTENCY_IN_PROGRESS` | 409 | Ver [Idempotencia](../api/idempotencia.md) |

## Rendimiento

- El detalle carga prioridad, estado, quien lo creó y quien lo atiende en una sola pasada (*eager loading*). El historial carga tipo y autor de la misma forma.
- Una prueba verifica que el detalle y el historial hacen **las mismas consultas** con poco o con mucho historial (sin N+1).
- Catálogos, tipos de actividad y permisos se leen una sola vez por petición.

## Capas

| Capa | Piezas |
|---|---|
| Domain | `IncidentAccess` (quién puede qué), `VisibilityScope` (qué ve cada uno), `IncidentSort`, `IncidentRules` (vencimiento, vencido, resolución), `Actor`, `IncidentState`, `StatusFlags`, `Denial`, `IncidentExceptions` |
| Application | Casos de uso `ListIncidents`, `CreateIncident`, `UpdateIncident`, `ChangeIncidentStatus`, `AssignIncident`, `DeleteIncident` (eliminar y restaurar); `IncidentGate` (carga y autoriza); datos (`IncidentRecord`, `IncidentInput`, `Person`, `PriorityInfo`); evento `IncidentChanged`; puertos |
| Infrastructure | `EloquentIncidentList`, `IncidentFilters` y `VisibilitySql` (listado y visibilidad en SQL), `EloquentIncidentRepository`, `IncidentMapper`, `EloquentIncidentCatalog`, `EloquentAssigneeDirectory`, `EloquentActivityLog`, `EloquentActivityReader`, `IncidentServiceProvider`. La hora y las transacciones vienen de [Shared](shared.md#puertos-que-comparten-los-módulos) |
| Http | `IncidentController`, `IncidentWorkflowController`, `CatalogController`, `IncidentRequest`, `WorkflowRequest`, `IncidentResource`, `RequestActor` |

## Aviso de cambios

Cada escritura del repositorio (crear, editar, cambiar de estado, asignar, eliminar y restaurar) emite `IncidentChanged` con el id y el ULID del incidente, qué le pasó (`IncidentChange`: creado, editado, eliminado o restaurado), quién lo creó y a quién estaba y queda asignado. El evento sale solo cuando la transacción se confirma (`ShouldDispatchAfterCommit`), así nadie reacciona a un cambio que al final se deshizo. Lo escuchan la caché del dashboard y el tiempo real, que avisa a las pantallas abiertas ([Módulo Realtime](realtime.md)).

## Visibilidad en SQL

`VisibilitySql` es la traducción a SQL de `VisibilityScope`. La usan el listado y el dashboard, así ambos cuentan exactamente lo mismo.

## Dónde está en el código

- [`backend/app/Modules/Incidents/`](../../backend/app/Modules/Incidents/)
- [`backend/app/Modules/Users/Infrastructure/UserPermissions.php`](../../backend/app/Modules/Users/Infrastructure/UserPermissions.php): permisos efectivos de una persona.
- Pruebas: [`tests/Unit/Incidents/`](../../backend/tests/Unit/Incidents/) y [`tests/Feature/Incidents/`](../../backend/tests/Feature/Incidents/)
- Tablas: [incidents](../base-de-datos/diccionario/incidents.md), [incident_activities](../base-de-datos/diccionario/incident-activities.md) y [catálogos](../base-de-datos/diccionario/catalogos.md)
- Decisiones: [ADR-0012](../decisiones/0012-bloqueo-optimista.md), [ADR-0021](../decisiones/0021-permisos-granulares.md), [ADR-0037](../decisiones/0037-idempotencia.md), [ADR-0043](../decisiones/0043-reglas-de-acceso-en-el-dominio.md) y [ADR-0044](../decisiones/0044-listado-de-incidentes.md)
