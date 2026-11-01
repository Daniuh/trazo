---
title: Cambiar de estado
description: Las transiciones permitidas del catálogo, la nota obligatoria en Resuelto y Cancelado, la confirmación en los estados finales y la fecha de resolución.
audience: tecnico
area: flujos
module: incidents
order: 3
---

# Cambiar de estado

El estado solo se mueve por las transiciones que define el catálogo (`status_transitions`):

- Abierto → En progreso o Cancelado
- En progreso → En espera, Resuelto o Cancelado
- En espera → En progreso o Cancelado
- Resuelto → Cerrado o En progreso

Cerrado y Cancelado son finales: de ahí solo se sale [reabriendo](reapertura.md). El detalle muestra un botón "Pasar a X" con el siguiente paso natural y el resto en "Cambiar a…"; el Kanban de [Mis incidentes](mis-incidentes.md) usa el mismo flujo al soltar una tarjeta.

```flujo
carriles: [Persona, Navegador, API, Base de datos, Cola y avisos]
pasos:
  - id: elige
    carril: Persona
    tipo: inicio
    texto: Elige el estado nuevo
    detalle: Solo se ofrecen los destinos que la API entrega para ese incidente; el navegador no conoce las transiciones.
    va: nota
  - id: nota
    carril: Navegador
    tipo: decision
    texto: ¿El destino pide nota o es final?
    detalle: 'Resuelto y Cancelado piden nota (`statuses.requires_note`); los finales piden además confirmación. Los demás estados aceptan una nota opcional ("Agregar una nota").'
    si: dialogo
    no: envia
  - id: dialogo
    carril: Persona
    texto: Escribe la nota y confirma
    va: envia
  - id: envia
    carril: Navegador
    texto: Envía PUT /incidents/{id}/status
    detalle: Con el código del estado, la versión conocida y la nota (hasta 1.000 caracteres).
    va: puede
  - id: puede
    carril: API
    tipo: decision
    texto: ¿Lo ve y puede cambiar su estado?
    detalle: 'Con `incidents.status.any`, o con `incidents.status.assigned` si lo tiene asignado. Nunca en uno cerrado, cancelado o eliminado.'
    si: version
    no: eneg
  - id: eneg
    carril: API
    tipo: error
    texto: Responde 404, 403 o 409
  - id: version
    carril: API
    tipo: decision
    texto: ¿Versión actual?
    si: permitido
    no: e409v
  - id: e409v
    carril: API
    tipo: error
    texto: Responde 409 VERSION_CONFLICT
  - id: permitido
    carril: Base de datos
    tipo: decision
    texto: ¿La transición está en el catálogo?
    si: falta
    no: e409t
  - id: e409t
    carril: Base de datos
    tipo: error
    texto: Responde 409 transición no permitida
  - id: falta
    carril: API
    tipo: decision
    texto: Si el destino pide nota, ¿la trae?
    si: resuelto
    no: e422
  - id: e422
    carril: API
    tipo: error
    texto: Responde 422 falta la nota
  - id: resuelto
    carril: API
    texto: Calcula la fecha de resolución
    detalle: 'Por las banderas del catálogo: a un estado pendiente, `resolved_at` vuelve a vacío; a uno no pendiente ni final (Resuelto), ahora; a uno final, se conserva la que tenía.'
    va: guarda
  - id: guarda
    carril: Base de datos
    texto: Guarda estado, versión e historial
    detalle: Un solo `UPDATE` con la versión en el `WHERE` y la entrada `status_changed` (de y a, con la nota) en la misma transacción.
    va: avisa
  - id: avisa
    carril: Cola y avisos
    texto: Avisa del cambio tras confirmar
    detalle: Caché del dashboard, `incident.changed` y notificación a quien lo creó y a quien lo tiene asignado (nunca a quien hizo el cambio).
    va: listo
  - id: listo
    carril: Navegador
    tipo: fin
    texto: Muestra el estado nuevo y su historial
```

## Qué ve la persona en cada error

| Código | HTTP | Cuándo | Mensaje en pantalla |
|---|---|---|---|
| `INVALID_STATUS_TRANSITION` | 409 | Ese destino no sale del estado actual (por ejemplo, otra persona lo movió) | Ese cambio de estado no está permitido desde el estado actual. Recarga la página para ver las opciones disponibles. |
| `STATUS_NOTE_REQUIRED` | 422 | Resuelto o Cancelado sin nota | Para este cambio de estado hace falta una nota. Escribe brevemente qué pasó y vuelve a intentarlo. |
| `VERSION_CONFLICT` | 409 | Alguien guardó otro cambio antes | Alguien más guardó cambios en este incidente mientras lo tenías abierto. Recarga para ver la versión actual antes de seguir. |
| `INCIDENT_FINALIZED` | 409 | Ya está cerrado o cancelado | Este incidente ya está cerrado o cancelado, así que no se puede modificar. |
| `FORBIDDEN_ACTION` | 403 | Sin permiso de estado, o no lo tiene asignado | No tienes permiso para hacer esta acción. Si crees que deberías tenerlo, habla con un administrador. |

## Dónde está en el código

- [`ChangeIncidentStatus`](../../../backend/app/Modules/Incidents/Application/ChangeIncidentStatus.php): transición, nota y fecha de resolución.
- [`IncidentRules`](../../../backend/app/Modules/Incidents/Domain/IncidentRules.php) (`resolvedAt`) e [`IncidentAccess`](../../../backend/app/Modules/Incidents/Domain/IncidentAccess.php).
- [`EloquentIncidentCatalog`](../../../backend/app/Modules/Incidents/Infrastructure/EloquentIncidentCatalog.php): transiciones del catálogo.
- [`useStatusChange`](../../../frontend/src/modules/incidents/presentation/useStatusChange.ts), [`useNoteDialog`](../../../frontend/src/modules/incidents/presentation/useNoteDialog.ts) e [`IncidentNoteDialog`](../../../frontend/src/modules/incidents/presentation/components/IncidentNoteDialog.vue).
- Explicación del módulo: [Módulo Incidents](../../backend/incidents.md).
