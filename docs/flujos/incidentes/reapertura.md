---
title: Solicitar y decidir una reapertura
description: Cómo vuelve a la vida un incidente cerrado o cancelado, por solicitud de cualquiera y decisión de quien supervisa, o directo con motivo.
audience: tecnico
area: flujos
module: incidents
order: 6
---

# Solicitar y decidir una reapertura

Un incidente cerrado o cancelado no se mueve con un cambio de estado normal. Hay dos caminos:

- **Solicitud:** cualquiera que lo vea (`incidents.reopen.request`) pide reabrirlo con un motivo. Queda pendiente hasta que otra persona con `incidents.reopen.approve` (Supervisor y Super admin) la aprueba o la rechaza.
- **Directo:** quien puede aprobar lo reabre de una vez con su propio motivo (`POST /incidents/{id}/reopen`).

Solo puede haber una solicitud pendiente por incidente: lo garantiza la base con una columna calculada única (`pending_incident_id`).

```flujo
carriles: [Solicitante, Navegador, API, Base de datos, Supervisión]
pasos:
  - id: pide
    carril: Solicitante
    tipo: inicio
    texto: Pide reabrir y escribe el motivo
    detalle: En el detalle de un incidente cerrado o cancelado, botón "Solicitar reapertura".
    va: envia
  - id: envia
    carril: Navegador
    texto: Envía POST /incidents/{id}/reopen-request
    va: puede
  - id: puede
    carril: API
    tipo: decision
    texto: ¿Terminado, sin otra solicitud y con permiso?
    si: abre
    no: eneg
  - id: eneg
    carril: API
    tipo: error
    texto: Responde 409, 403 o 404
  - id: abre
    carril: Base de datos
    texto: Guarda la solicitud y anota "reopen_requested"
    detalle: 'El motivo va en la nota del historial. El listado lo marca "Reapertura pendiente" y se puede filtrar (`?reopen=1`). Quien puede decidir recibe la notificación "reapertura pendiente".'
    va: ve
  - id: ve
    carril: Supervisión
    texto: Ve la solicitud en el detalle o la vista previa
    detalle: El aviso `ReopenRequestNotice` muestra quién la pidió, cuándo y por qué, con Aprobar y Rechazar.
    va: propia
  - id: propia
    carril: API
    tipo: decision
    texto: ¿Es su propia solicitud?
    detalle: Nadie decide la suya; el botón no aparece y la API responde 403 si se intenta.
    si: e403
    no: decide
  - id: e403
    carril: API
    tipo: error
    texto: Responde 403 REOPEN_OWN_REQUEST
  - id: decide
    carril: Supervisión
    tipo: decision
    texto: ¿Aprueba?
    si: aprueba
    no: rechaza
  - id: rechaza
    carril: Base de datos
    texto: Cierra la solicitud como rechazada, con motivo
    detalle: 'El incidente sigue como estaba. Historial `reopen_rejected`; avisa a quien pidió. Si otra persona la decidió primero, responde 409 `REOPEN_NOT_REQUESTED`.'
    va: rechazada
  - id: rechazada
    carril: Navegador
    tipo: fin
    texto: Queda terminado con el rechazo en el historial
  - id: aprueba
    carril: Base de datos
    texto: Cierra la solicitud y reabre el incidente
    detalle: 'En una sola transacción y con la versión en el `UPDATE`: vuelve a En progreso si tiene asignado o al estado inicial si no, `resolved_at` vacío y vencimiento nuevo según la prioridad. Historial `reopened` con el motivo de quien pidió.'
    va: avisa
  - id: avisa
    carril: Navegador
    tipo: fin
    texto: El incidente vuelve a estar en curso
    detalle: Notificación a quien lo creó, al asignado y a quien pidió la reapertura.
```

## Qué ve la persona en cada error

| Código | HTTP | Cuándo | Mensaje en pantalla |
|---|---|---|---|
| `INCIDENT_NOT_FINALIZED` | 409 | El incidente sigue en curso | Este incidente sigue en curso, así que no hay nada que reabrir. Recarga la página para ver su estado actual. |
| `REOPEN_ALREADY_REQUESTED` | 409 | Ya hay una solicitud pendiente | Ya hay una solicitud para reabrir este incidente esperando respuesta. Recarga la página para verla. |
| `REOPEN_NOT_REQUESTED` | 409 | Otra persona ya respondió la solicitud | Esta solicitud de reapertura ya fue respondida por otra persona. Recarga la página para ver cómo quedó. |
| `REOPEN_OWN_REQUEST` | 403 | Quien pidió intenta decidir | No puedes decidir tu propia solicitud de reapertura. Otra persona con ese permiso debe hacerlo. |
| `VERSION_CONFLICT` | 409 | El incidente cambió mientras se decidía | Alguien más guardó cambios en este incidente mientras lo tenías abierto. Recarga para ver la versión actual antes de seguir. |

## Dónde está en el código

- [`RequestIncidentReopen`](../../../backend/app/Modules/Incidents/Application/RequestIncidentReopen.php), [`ReopenIncident`](../../../backend/app/Modules/Incidents/Application/ReopenIncident.php) (`handle` directo y `approve`) y [`RejectIncidentReopen`](../../../backend/app/Modules/Incidents/Application/RejectIncidentReopen.php).
- [`IncidentAccess`](../../../backend/app/Modules/Incidents/Domain/IncidentAccess.php): reglas `RequestReopen`, `Reopen` y `DecideReopen`.
- [`EloquentReopenRequests`](../../../backend/app/Modules/Incidents/Infrastructure/EloquentReopenRequests.php): una pendiente por incidente y decisión condicional.
- [`ReopenRequestNotice`](../../../frontend/src/modules/incidents/presentation/components/ReopenRequestNotice.vue) y [`useReopenDecision`](../../../frontend/src/modules/incidents/presentation/useReopenDecision.ts).
- Explicación del módulo: [Módulo Incidents](../../backend/incidents.md).
