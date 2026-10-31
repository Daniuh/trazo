---
title: Módulo Incidents
description: Crear, ver, editar, cambiar de estado (con su nota), asignar, reabrir, eliminar y restaurar incidentes, con sus reglas de acceso.
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
| `GET` | `/incidents/mine` | "Mis incidentes": todos los asignados a quien pregunta, en curso o terminados hace 7 días o menos, por vencimiento, con `actions` y `next_statuses` y hasta 500 (`meta.truncated`, `limit`, `finished_days`) ([Mis incidentes](#mis-incidentes)) | `incidents.view.assigned` |
| `GET` | `/incidents/export` | Lo mismo del listado, sin página, en un archivo CSV ([Exportación](#exportación)) | `incidents.export`; 5 por minuto |
| `POST` | `/incidents` | Crear (acepta `Idempotency-Key`) | `incidents.create` |
| `GET` | `/incidents/{id}` | Detalle con `actions`, `next_statuses` y `comments_count` | Verlo |
| `PUT` | `/incidents/{id}` | Editar título, descripción, prioridad y vencimiento | Ver la tabla de reglas |
| `PUT` | `/incidents/{id}/status` | Cambiar el estado, con `note` (obligatoria donde el catálogo lo pide) | Ver la tabla de reglas |
| `PUT` | `/incidents/{id}/assignee` | Tomar, asignar o quitar la asignación (`null`) | Ver la tabla de reglas |
| `DELETE` | `/incidents/{id}` | Borrado lógico | `incidents.delete` |
| `POST` | `/incidents/{id}/restore` | Restaurar | `incidents.restore` |
| `GET` | `/incidents/{id}/activities` | Historial, de lo más reciente a lo más antiguo, 50 por página | Verlo |
| `POST` | `/incidents/{id}/reopen-request` | Pedir que se reabra, con `reason` ([Reapertura](#reapertura)) | `incidents.reopen.request` |
| `POST` | `/incidents/{id}/reopen-request/approve` | Aprobar la solicitud pendiente (con `version`) | `incidents.reopen.approve` |
| `POST` | `/incidents/{id}/reopen-request/reject` | Rechazarla, con `reason` | `incidents.reopen.approve` |
| `POST` | `/incidents/{id}/reopen` | Reabrir directamente, con `reason` y `version` | `incidents.reopen.approve` |
| `GET` | `/incidents/{id}/comments` | Conversación, del mensaje más nuevo al más viejo, 50 por página (`before` para los anteriores) ([Conversación](#conversación)) | Verlo |
| `POST` | `/incidents/{id}/comments` | Escribir un mensaje (`body`, hasta 2.000 caracteres) | `incidents.comment`; 20 por minuto |
| `DELETE` | `/incidents/{id}/comments/{comentario}` | Borrar un mensaje propio, durante 5 minutos | Quien lo escribió |

Todas las rutas van bajo `/api/v1` y exigen sesión. `{id}` es el ULID del incidente. Las escrituras sobre un incidente existente exigen `version`, la que se conocía al abrirlo.

### Respuesta del detalle

```json
{
  "data": {
    "id": "01m5j1sf78wr8p15zs84s3gdaq",
    "code": "TZ-26-0001",
    "title": "No carga facturación",
    "description": "…",
    "priority": { "code": "high", "name": "Alta", "level": 3 },
    "status": { "code": "in_progress", "name": "En progreso", "is_final": false },
    "creator": { "id": "01m5…", "name": "Juan Pérez" },
    "assignee": { "id": "01m5…", "name": "Laura Gómez" },
    "due_date": "2026-10-23T14:56:07+00:00",
    "is_overdue": false,
    "reopen_requested": false,
    "resolved_at": null,
    "created_at": "…", "updated_at": "…", "deleted_at": null,
    "version": 3,
    "actions": {
      "update": true, "change_status": true, "take": false, "assign": true, "delete": true, "restore": false,
      "request_reopen": false, "reopen": false, "decide_reopen": false
    },
    "next_statuses": [
      { "code": "on_hold", "name": "En espera", "requires_note": false },
      { "code": "resolved", "name": "Resuelto", "requires_note": true }
    ],
    "reopen_request": null
  }
}
```

- **`actions`** indica qué puede hacer esta persona con este incidente, ahora. El front muestra u oculta botones solo con esto.
- **`next_statuses`** son los estados a los que esta persona puede llevarlo, y si exigen una nota. Si no puede cambiar el estado, viene vacío.
- **`reopen_request`** es la solicitud de reapertura pendiente (`requested_by`, `reason`, `created_at`), o `null`.
- **`reopen_requested`** dice si hay una solicitud de reapertura pendiente. Va también en el listado, que no trae el detalle de la solicitud.
- **`is_overdue`** se calcula en el back: el estado es pendiente y el vencimiento ya pasó.
- **`deadline`** dice cómo va con su plazo, también calculado en el back (`IncidentRules::deadline`):
  - `bucket`: `overdue` (vencido), `today` (vence antes de que termine el día del negocio, `BUSINESS_TIMEZONE`), `week` (en los 7 días siguientes), `later` o `finished` (resuelto, cerrado o cancelado).
  - `time_used`: en los pendientes, cuánto del plazo se ha gastado, de 0 a 100 (tiempo desde la creación sobre el plazo total). `null` en los terminados.
  - `resolved_on_time`: en los terminados, si se resolvió antes del vencimiento. `null` si no tiene fecha de resolución (p. ej. cancelado sin resolver).

## Listado

```
GET /api/v1/incidents?page=2&per_page=20&sort=-due_date&group_by=due&search=impresora
    &filter[status]=open,in_progress&filter[priority]=high,critical
    &filter[assignee]=none,01m5…&filter[creator]=01m5…
    &filter[created_from]=2026-10-01T00:00:00Z&filter[due_to]=2026-10-31T23:59:59Z
    &filter[overdue]=1&filter[deleted]=1&filter[reopen_requested]=1
```

| Parámetro | Detalle |
|---|---|
| `page`, `per_page` | Página (desde 1) y tamaño: 20 por defecto, máximo 100 |
| `sort` | `created_at` (por defecto, descendente), `due_date`, `updated_at`, `priority`, `status` o `code`. Con `-` delante, descendente. Siempre se desempata por id |
| `group_by` | `due`, `status`, `priority` o `assignee` (opcional). Ordena primero por grupo y dentro de él por `sort`, así cada grupo sale entero y seguido entre páginas. Vencimiento: vencidos, hoy, 7 días, más adelante y terminados; estado: orden del flujo; prioridad: de la más alta a la más baja; asignado: sin asignar primero y luego por nombre ([ADR-0080](../decisiones/0080-lista-agrupada-con-plazo.md)) |
| `search` | Al menos una palabra de 3 letras; cada palabra es obligatoria y se busca por prefijo en el título y la descripción. Un código con su año (`TZ-26-0142`, `26-142`) se busca exacto; un número suelto se busca como texto |
| `filter[status]`, `filter[priority]` | Códigos del catálogo, separados por comas |
| `filter[assignee]` | ULIDs separados por comas; `none` agrega "sin asignar" |
| `filter[creator]` | ULID de quien lo reportó |
| `filter[created_from]`, `filter[created_to]`, `filter[due_from]`, `filter[due_to]` | Fechas ISO 8601 (inclusive) |
| `filter[overdue]` | `1`: solo pendientes con el vencimiento pasado |
| `filter[deleted]` | `1`: solo eliminados. Requiere `incidents.restore` (si no, 403) |
| `filter[reopen_requested]` | `1`: solo los que tienen una solicitud de reapertura pendiente |

- **Respuesta:** `data` trae una versión liviana de cada incidente (sin descripción, estados siguientes ni detalle de la reapertura, pero con `actions`, `is_overdue` y `reopen_requested`). El listado y el detalle cargan las mismas relaciones (`IncidentMapper::RELATIONS`), así sus `actions` siempre coinciden. `meta` trae `page`, `per_page`, `total`, `last_page` y `groups`: con `group_by`, el total de cada grupo con los mismos filtros, como `[{ "key": "overdue", "total": 42 }]` (la clave es el `deadline.bucket`, el código del estado o la prioridad, o el ULID del asignado, `none` sin asignar). Sin `group_by`, viene vacío. Agrupado, la página no se saca ordenando todo: se cuenta cada grupo, se ve en cuáles cae la página y se pide solo esos ([ADR-0080](../decisiones/0080-lista-agrupada-con-plazo.md)).
- **Errores de validación:** `OUT_OF_RANGE` (tamaño de página), `INVALID_OPTION` (orden o grupo), `SEARCH_TOO_SHORT` (búsqueda) e `INVALID_REFERENCE` (código de catálogo inexistente).
- **Visibilidad:** la misma regla del detalle (`VisibilityScope`), aplicada en SQL; una prueba compara ambos resultados para cada persona. Ver [ADR-0044](../decisiones/0044-listado-de-incidentes.md).
- **Rendimiento:** validado con `EXPLAIN` sobre 100.000 incidentes. La mayoría de los escenarios responde en menos de 100 ms; los dos que pueden superarlo con la máquina cargada están anotados en [Rendimiento](../base-de-datos/rendimiento.md).

## Mis incidentes

El tablero de quien atiende incidentes ([ADR-0078](../decisiones/0078-mis-incidentes.md)). `ListMyIncidents` exige `incidents.view.assigned` (sin él, 403) y pide a `MyIncidentsQuery`:

- Asignados a la persona y no eliminados.
- Los que no están en un estado final, más los finales con `updated_at` de los últimos 7 días (`FINISHED_DAYS`). Un incidente terminado ya no cambia, así que su `updated_at` es cuándo terminó.
- Por vencimiento y luego por id, hasta 500 (`LIMIT`). Se pide uno de más para saber si quedaron fuera (`truncated`).

Cada incidente sale con `IncidentResource::board`: el resumen del listado más `next_statuses`, para que el Kanban sepa a qué columnas se puede soltar cada tarjeta. Mover una tarjeta usa el mismo `PUT /incidents/{id}/status` del detalle.

**Rendimiento:** usa el índice `dashboard_assignee` (`assigned_to` primero); medido en 1,5 ms. Las consultas no crecen con la cantidad de tarjetas (prueba N+1 en `MyIncidentsTest`).

## Exportación

`GET /api/v1/incidents/export` recibe los mismos parámetros del listado (salvo `page` y `per_page`) y responde un archivo CSV que se va escribiendo por partes. Detalles y alternativas en el [ADR-0058](../decisiones/0058-exportacion-en-la-practica.md).

- **Quién:** con `incidents.export` (Supervisor y Super admin, o concedido a la persona). Para exportar eliminados también hace falta `incidents.restore`. Lo que sale es exactamente lo que la persona ve en el listado con esos filtros, en el mismo orden.
- **Tope:** 50.000 filas (`EXPORT_MAX_ROWS`). Se cuentan antes de empezar; si se pasa, responde 422 `EXPORT_TOO_LARGE` con `params.total` y `params.max`.
- **Límite:** 5 exportaciones por minuto por persona (`throttle:export`), además del general de la API.
- **Archivo:** `incidentes-AAAA-MM-DD-HHMM.csv` (hora del negocio), `text/csv; charset=UTF-8`, sin caché.
  - UTF-8 con BOM y punto y coma como separador, para que el Excel en español lo abra en columnas y con tildes.
  - Columnas: Código, Título, Estado, Prioridad, Creado por, Asignado a ("Sin asignar" si nadie), Creado, Vencimiento, Resuelto (vacío si no) y Vencido (Sí/No). La descripción no va.
  - Fechas `AAAA-MM-DD HH:MM` en `BUSINESS_TIMEZONE`, con el desfase en el encabezado: "Creado (UTC-05:00)".
  - Una celda que empiece por `=`, `+`, `-`, `@`, tabulador o salto de línea lleva un apóstrofo delante: Excel la muestra como texto en lugar de ejecutarla.
- **Registro:** cada exportación queda en `admin_actions` (tipo `incidents_exported`) con el número de filas y los filtros usados, y aparece en [Auditoría](audit.md). Se anota al pedirla, antes de escribir el archivo.
- **Memoria:** las filas se leen en bloques de 1.000 (`trazo.export.chunk_size`) con solo las columnas del archivo. Medido en el Docker local con 50.000 filas: 7,9 MB en 2,1 s, con el proceso estable en 28 MB (2 MB más que en reposo); con 100.000 filas el pico es el mismo.

## Reglas de acceso

| Acción | Quién | Además |
|---|---|---|
| Ver | `view.any` todos; `view.own` los que creó; `view.assigned` los asignados a él; `view.unassigned` los que nadie tiene | Si no puede verlo, la API responde **404**, como si no existiera |
| Editar | `update.any` cualquiera; `update.own` los que creó, **solo mientras están en el estado inicial (Abierto)** | Nunca en un estado final (Cerrado o Cancelado) |
| Cambiar estado | `status.any` cualquiera; `status.assigned` los asignados a él | Solo por las transiciones del catálogo; nunca desde un estado final |
| Pedir la reapertura | `incidents.reopen.request` | Solo si está cerrado o cancelado y no hay otra solicitud pendiente |
| Reabrir directamente | `incidents.reopen.approve` | Igual que pedirla: si hay una solicitud pendiente, se decide esa |
| Aprobar o rechazar | `incidents.reopen.approve` | Debe haber una solicitud pendiente y no puede ser propia |
| Tomar | `incidents.take` | Solo si nadie lo tiene |
| Asignar o quitar | `incidents.assign` | Solo a personas activas que atienden incidentes; nunca en un estado final |
| Eliminar / restaurar | `incidents.delete` / `incidents.restore` | Un eliminado solo lo ve quien puede restaurarlo |

Las reglas consultan **permisos**, nunca nombres de rol. Están en una sola clase de dominio, `IncidentAccess`, que usan los casos de uso y el campo `actions`. Así, lo que el front muestra y lo que la API permite nunca difieren.

**Asignar al crear:** el campo `assignee` de `POST /incidents` es opcional. Con `incidents.assign` admite a cualquier persona que atiende incidentes; con solo `incidents.take`, únicamente a quien crea (`IncidentAccess::canAssignOnCreate`, [ADR-0072](../decisiones/0072-asignarse-al-crear.md)). Quien no puede asignar a otros recibe siempre 403, para no revelar a quién se podría asignar. Al editar, la asignación se cambia con su propio endpoint.

## Reglas de negocio

- **Estado inicial:** todo incidente nace en el estado marcado como inicial en el catálogo (Abierto).
- **Título:** de 5 a 80 caracteres, sin contar los espacios de los extremos (`TOO_SHORT` / `TOO_LONG`, [ADR-0073](../decisiones/0073-largo-del-titulo.md)). Los que ya pasan de 80 se ven igual, pero al editarlos hay que acortarlos.
- **Vencimiento:** si quien crea no lo indica, se calcula con el tiempo de resolución de la prioridad (Baja 5 días, Media 3, Alta 1 y Crítica 4 horas). Un vencimiento nuevo no puede quedar en el pasado. Al editar, solo se envía si cambia, para que un incidente ya vencido se pueda seguir editando.
- **Fecha de resolución** (`resolved_at`), decidida por los datos del catálogo y no por códigos fijos:
  - al volver a un estado pendiente se borra;
  - al llegar a uno no pendiente y no final (Resuelto), se marca;
  - al cerrar o cancelar, se conserva.
- **Código legible:** prefijo, año y consecutivo del año, por ejemplo `TZ-26-0142` (`IncidentCode`). El número sale del contador del año (`incident_code_sequences`), que `IncidentCodes::next` toma con bloqueo dentro de la misma transacción que crea el incidente: dos creaciones a la vez nunca reciben el mismo número. El año es el de la zona horaria del negocio y cada año empieza en 1; desde el 10.000 el número sigue con más cifras. El prefijo sale de `INCIDENT_CODE_PREFIX` (por defecto `TZ`). Nunca se repite ni se reutiliza [ADR-0074](../decisiones/0074-codigo-de-incidente-por-ano.md).
- **Bloqueo optimista:** cada escritura comprueba la versión en el mismo `UPDATE … WHERE lock_version = ?`. Si otra persona guardó entretanto, responde 409 `VERSION_CONFLICT` y no se pierde ningún cambio.
- **Solo lo que cambió:** editar sin cambios no sube la versión ni deja registro.
- **Nota al cambiar de estado:** los estados con `requires_note` en el catálogo (Resuelto y Cancelado) la exigen; en los demás es opcional. Hasta 1.000 caracteres; una nota con solo espacios cuenta como vacía. Se guarda en el historial ([ADR-0071](../decisiones/0071-nota-de-estado-y-reapertura.md)).

## Reapertura

Cerrado y Cancelado no tienen transiciones de salida: no se reabren cambiando el estado, sino por este camino aparte ([ADR-0071](../decisiones/0071-nota-de-estado-y-reapertura.md)).

- **Pedirla:** quien puede ver el incidente terminado y tiene `incidents.reopen.request` envía el motivo. Queda en `incident_reopen_requests` como pendiente; solo puede haber una por incidente (la base lo garantiza con un índice único).
- **Decidirla:** quien tiene `incidents.reopen.approve` y no es quien la pidió la aprueba o la rechaza. Rechazar exige un motivo, que queda en la solicitud y en el historial.
  - La decisión es un `UPDATE` condicionado a que siga pendiente. Si otra persona la decidió primero, responde `REOPEN_NOT_REQUESTED`.
- **Reabrir:** al aprobar, o directamente con su propio motivo:
  - vuelve a En progreso si tiene a alguien asignado, o al estado inicial si no;
  - `resolved_at` se borra;
  - el vencimiento se recalcula con el tiempo de la prioridad, desde ahora.
- **Aviso:** pedir y rechazar no cambian columnas del incidente (su versión sigue igual), pero emiten `IncidentChanged` para que las pantallas abiertas se enteren.

## Conversación

Cada incidente tiene una conversación tipo chat para preguntar y aclarar sin salir de Trazo [ADR-0076](../decisiones/0076-conversacion-del-incidente.md).

- **Quién lee y escribe:** lee quien puede ver el incidente; escribe quien además tiene `incidents.comment` (todos los roles base). Se puede escribir también en un incidente cerrado o cancelado, para un seguimiento: el estado no cambia. Un incidente eliminado no admite mensajes.
- **Borrar:** solo quien escribió el mensaje y solo durante 5 minutos (`CommentRules::DELETE_WINDOW_MINUTES`). La fila queda, el texto se vacía y la conversación muestra "Mensaje eliminado". No se editan mensajes: lo que otros ya leyeron o respondieron no cambia.
- **Respuesta de cada mensaje:** `id` (ULID), `author`, `body` (`null` si se borró), `mine`, `created_at`, `deleted_at` y `actions.delete`. El listado trae `meta.total` (sin contar los borrados) y `meta.before` para pedir los anteriores.
- **Fuera del historial:** los mensajes no se anotan en `incident_activities`, para que el historial siga mostrando solo cambios del incidente.
- **Aviso:** escribir y borrar emiten `IncidentCommented`, que el tiempo real convierte en `incident.commented` para los mismos canales que un cambio del incidente.

## Historial

Cada escritura deja un registro en `incident_activities` dentro de la misma transacción: si el registro falla, el cambio tampoco se guarda.

| Tipo | Cuándo | `changes` |
|---|---|---|
| `created` | Al crear | La asignación inicial, si la hubo |
| `updated` | Al editar | Cada campo cambiado: `{ "from": …, "to": … }` |
| `status_changed` | Al cambiar de estado | Estado anterior y nuevo, con código y nombre |
| `assigned` / `unassigned` | Al asignar o quitar | Persona anterior y nueva, con id y nombre |
| `deleted` / `restored` | Al eliminar o restaurar | — |
| `reopen_requested` | Al pedir la reapertura | — (el motivo va en `note`) |
| `reopen_rejected` | Al rechazarla | — (el motivo va en `note`) |
| `reopened` | Al reabrir | Estado y vencimiento, anterior y nuevo; el motivo va en `note` |

La columna `note` guarda lo que la persona escribió: la solución, el motivo de una cancelación o de una reapertura. El historial la devuelve en cada registro.

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
| `EXPORT_TOO_LARGE` | 422 | La exportación pasa del tope de filas (`params.total`, `params.max`) |
| `STATUS_NOTE_REQUIRED` | 422 | El estado de destino exige una nota y no llegó (`params.status`) |
| `INCIDENT_NOT_FINALIZED` | 409 | Pide o hace una reapertura de algo que sigue en curso |
| `REOPEN_ALREADY_REQUESTED` | 409 | Ya hay una solicitud pendiente |
| `REOPEN_NOT_REQUESTED` | 409 | No hay solicitud pendiente (o otra persona ya la decidió) |
| `REOPEN_OWN_REQUEST` | 403 | Intenta decidir su propia solicitud |
| `COMMENT_NOT_OWN` | 403 | Intenta borrar un mensaje de otra persona |
| `COMMENT_DELETE_EXPIRED` | 409 | Pasaron más de 5 minutos desde que escribió el mensaje |
| `COMMENT_ALREADY_DELETED` | 409 | El mensaje ya estaba borrado |

## Rendimiento

- El detalle carga prioridad, estado, quien lo creó y quien lo atiende en una sola pasada (*eager loading*). El historial carga tipo y autor de la misma forma.
- Una prueba verifica que el detalle y el historial hacen **las mismas consultas** con poco o con mucho historial (sin N+1).
- Catálogos, tipos de actividad y permisos se leen una sola vez por petición.

## Capas

| Capa | Piezas |
|---|---|
| Domain | `IncidentAccess` (quién puede qué), `VisibilityScope` (qué ve cada uno), `IncidentSort`, `IncidentRules` (vencimiento, vencido, resolución), `Actor`, `IncidentState`, `StatusFlags`, `Denial`, `IncidentExceptions` |
| Application | Casos de uso `ListIncidents`, `ListMyIncidents` (con su puerto `MyIncidentsQuery`), `ExportIncidents`, `CreateIncident`, `UpdateIncident`, `ChangeIncidentStatus`, `AssignIncident`, `DeleteIncident` (eliminar y restaurar), `RequestIncidentReopen`, `ReopenIncident` (directo o aprobando) y `RejectIncidentReopen`; puerto `ReopenRequests`; `IncidentGate` (carga y autoriza); datos (`IncidentRecord`, `IncidentInput`, `Person`, `PriorityInfo`, `IncidentExport`, `IncidentExportRow`); eventos `IncidentChanged`, `IncidentCommented` e `IncidentActivityRecorded`; puertos (entre ellos `IncidentExportQuery`) |
| Infrastructure | `EloquentIncidentList`, `EloquentMyIncidents`, `EloquentIncidentExport`, `IncidentFilters` (visibilidad, filtros y orden que comparten el listado y la exportación) y `VisibilitySql`, `EloquentIncidentRepository`, `IncidentMapper`, `EloquentIncidentCatalog`, `EloquentAssigneeDirectory`, `EloquentActivityLog`, `EloquentActivityReader`, `EloquentReopenRequests`, `IncidentServiceProvider`. La hora y las transacciones vienen de [Shared](shared.md#puertos-que-comparten-los-módulos) |
| Http | `IncidentController`, `IncidentWorkflowController`, `IncidentReopenController`, `ReopenRequest`, `CatalogController`, `IncidentRequest`, `WorkflowRequest`, `IncidentResource`, `IncidentCsv` (forma del archivo exportado), `RequestActor` |

## Aviso de cambios

Cada escritura del repositorio (crear, editar, cambiar de estado, asignar, eliminar y restaurar) emite `IncidentChanged` con el id y el ULID del incidente, qué le pasó (`IncidentChange`: creado, editado, eliminado o restaurado), quién lo creó y a quién estaba y queda asignado. El evento sale solo cuando la transacción se confirma (`ShouldDispatchAfterCommit`), así nadie reacciona a un cambio que al final se deshizo. Lo escuchan la caché del dashboard y el tiempo real, que avisa a las pantallas abiertas ([Módulo Realtime](realtime.md)).

Además, cada registro del historial emite `IncidentActivityRecorded` (id del registro, incidente, tipo y quién lo hizo), también al confirmarse. Lo escucha el [centro de notificaciones](notifications.md), que decide a quién avisar según el tipo.

## Visibilidad en SQL

`VisibilitySql` es la traducción a SQL de `VisibilityScope`. La usan el listado, la exportación y el dashboard, así todos cuentan exactamente lo mismo.

## Dónde está en el código

- [`backend/app/Modules/Incidents/`](../../backend/app/Modules/Incidents/)
- Plazo y grupos: [`IncidentRules::deadline`](../../backend/app/Modules/Incidents/Domain/IncidentRules.php), [`DueWindow`](../../backend/app/Modules/Incidents/Domain/DueWindow.php), [`DueBucket`](../../backend/app/Modules/Incidents/Domain/DueBucket.php), [`IncidentGroup`](../../backend/app/Modules/Incidents/Domain/IncidentGroup.php), [`DueWindows`](../../backend/app/Modules/Incidents/Application/DueWindows.php), el conteo, el filtro y el orden de cada grupo en [`IncidentGroups`](../../backend/app/Modules/Incidents/Infrastructure/IncidentGroups.php) (con [`IncidentGroupSlice`](../../backend/app/Modules/Incidents/Infrastructure/IncidentGroupSlice.php)) y la página por grupos en [`EloquentIncidentList`](../../backend/app/Modules/Incidents/Infrastructure/EloquentIncidentList.php); pruebas en [`ListIncidentGroupsTest.php`](../../backend/tests/Feature/Incidents/ListIncidentGroupsTest.php) e [`IncidentRulesTest.php`](../../backend/tests/Unit/Incidents/IncidentRulesTest.php)
- [`backend/app/Modules/Users/Infrastructure/UserPermissions.php`](../../backend/app/Modules/Users/Infrastructure/UserPermissions.php): permisos efectivos de una persona.
- Pruebas: [`tests/Unit/Incidents/`](../../backend/tests/Unit/Incidents/) y [`tests/Feature/Incidents/`](../../backend/tests/Feature/Incidents/)
- Tablas: [incidents](../base-de-datos/diccionario/incidents.md), [incident_activities](../base-de-datos/diccionario/incident-activities.md), [incident_reopen_requests](../base-de-datos/diccionario/incident-reopen-requests.md), [incident_comments](../base-de-datos/diccionario/incident-comments.md) y [catálogos](../base-de-datos/diccionario/catalogos.md)
- Reapertura: [`ReopenIncident`](../../backend/app/Modules/Incidents/Application/ReopenIncident.php), [`EloquentReopenRequests`](../../backend/app/Modules/Incidents/Infrastructure/EloquentReopenRequests.php) e [`IncidentReopenController`](../../backend/app/Modules/Incidents/Http/IncidentReopenController.php); pruebas en [`ReopenIncidentTest.php`](../../backend/tests/Feature/Incidents/ReopenIncidentTest.php)
- Conversación: [`IncidentConversation`](../../backend/app/Modules/Incidents/Application/IncidentConversation.php), [`CommentRules`](../../backend/app/Modules/Incidents/Domain/CommentRules.php), [`DatabaseIncidentComments`](../../backend/app/Modules/Incidents/Infrastructure/DatabaseIncidentComments.php) e [`IncidentCommentController`](../../backend/app/Modules/Incidents/Http/IncidentCommentController.php); pruebas en [`IncidentConversationTest.php`](../../backend/tests/Feature/Incidents/IncidentConversationTest.php) y [`CommentRulesTest.php`](../../backend/tests/Unit/Incidents/CommentRulesTest.php)
- Decisiones: [ADR-0012](../decisiones/0012-bloqueo-optimista.md), [ADR-0021](../decisiones/0021-permisos-granulares.md), [ADR-0037](../decisiones/0037-idempotencia.md), [ADR-0043](../decisiones/0043-reglas-de-acceso-en-el-dominio.md), [ADR-0044](../decisiones/0044-listado-de-incidentes.md), [ADR-0058](../decisiones/0058-exportacion-en-la-practica.md) y [ADR-0071](../decisiones/0071-nota-de-estado-y-reapertura.md)
- Exportación: [`ExportIncidents`](../../backend/app/Modules/Incidents/Application/ExportIncidents.php), [`EloquentIncidentExport`](../../backend/app/Modules/Incidents/Infrastructure/EloquentIncidentExport.php), [`IncidentCsv`](../../backend/app/Modules/Incidents/Http/IncidentCsv.php) y [`CsvDownload`](../../backend/app/Modules/Shared/Http/CsvDownload.php); pruebas en [`ExportIncidentsTest.php`](../../backend/tests/Feature/Incidents/ExportIncidentsTest.php)
