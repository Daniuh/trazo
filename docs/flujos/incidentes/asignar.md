---
title: Asignar, tomar y reasignar
description: Cómo un incidente pasa a manos de alguien, la diferencia entre tomarlo y asignarlo, y qué pasa si dos personas lo toman a la vez.
audience: tecnico
area: flujos
module: incidents
order: 4
---

# Asignar, tomar y reasignar

Hay dos permisos distintos:

- **Tomar** (`incidents.take`): asignárselo a uno mismo, y solo si nadie lo tiene. Lo usan los agentes.
- **Asignar** (`incidents.assign`): elegir a cualquier persona que pueda recibir incidentes, cambiarla o dejarlo sin asignar. Lo usan supervisión y el Super admin.

Los dos van por el mismo endpoint; la API decide cuál aplica.

```flujo
carriles: [Persona, Navegador, API, Base de datos, Cola y avisos]
pasos:
  - id: pulsa
    carril: Persona
    tipo: inicio
    texto: Pulsa Tomar, Asignar o Reasignar
    detalle: En la ficha del detalle. "Asignar" abre `AssignDialog` con las personas de `GET /incidents/assignees` (solo activas y con permiso de recibir incidentes).
    va: envia
  - id: envia
    carril: Navegador
    texto: Envía PUT /incidents/{id}/assignee
    detalle: Con el ULID de la persona (o vacío para dejarlo sin asignar) y la versión conocida.
    va: ve
  - id: ve
    carril: API
    tipo: decision
    texto: ¿Lo puede ver y la persona existe?
    detalle: Si no lo ve, 404. Si la persona elegida no puede recibir incidentes, 422.
    si: cual
    no: eneg
  - id: eneg
    carril: API
    tipo: error
    texto: Responde 404 o 422
  - id: cual
    carril: API
    tipo: decision
    texto: ¿Se lo asigna a sí mismo y puede tomarlo?
    detalle: Si sí, se evalúa como "tomar"; cualquier otro caso se evalúa como "asignar".
    va:
      - { a: tomar, etiqueta: Tomar }
      - { a: asignar, etiqueta: Asignar }
  - id: tomar
    carril: API
    tipo: decision
    texto: ¿Sigue sin asignar y no terminó?
    si: version
    no: e409a
  - id: e409a
    carril: API
    tipo: error
    texto: Responde 409 ya lo tomaron
  - id: asignar
    carril: API
    tipo: decision
    texto: ¿Tiene permiso de asignar y no terminó?
    si: version
    no: e403
  - id: e403
    carril: API
    tipo: error
    texto: Responde 403 o 409
  - id: version
    carril: API
    tipo: decision
    texto: ¿Versión actual?
    detalle: Si dos personas lo toman a la vez, la segunda llega con una versión vieja y recibe el conflicto.
    si: guarda
    no: e409v
  - id: e409v
    carril: API
    tipo: error
    texto: Responde 409 VERSION_CONFLICT
  - id: guarda
    carril: Base de datos
    texto: Guarda el asignado e historial
    detalle: 'Entrada `assigned` o `unassigned` con la persona de antes y la de después (id y nombre, para que el historial no cambie si luego cambia su nombre). Si ya era la misma persona, no escribe nada.'
    va: avisa
  - id: avisa
    carril: Cola y avisos
    texto: Avisa a quien lo recibe
    detalle: '`incident.changed` a los canales que pueden verlo, incluido el de "sin asignar" si lo estaba antes; `incident.assigned` con "Te asignaron TZ-26-0142" a quien lo recibe (no si se lo tomó él mismo) y su notificación.'
    va: listo
  - id: listo
    carril: Navegador
    tipo: fin
    texto: Muestra la persona asignada
```

## Qué ve la persona en cada error

| Código | HTTP | Cuándo | Mensaje en pantalla |
|---|---|---|---|
| `INCIDENT_ALREADY_ASSIGNED` | 409 | Otra persona lo tomó antes | Otra persona tomó este incidente hace un momento. Recarga la página para ver quién lo tiene. |
| `VERSION_CONFLICT` | 409 | Alguien guardó otro cambio antes | Alguien más guardó cambios en este incidente mientras lo tenías abierto. Recarga para ver la versión actual antes de seguir. |
| `INVALID_ASSIGNEE` | 422 | La persona elegida no puede recibir incidentes | Esta persona no puede recibir incidentes. Elige a alguien de la lista. |
| `INCIDENT_FINALIZED` | 409 | Está cerrado o cancelado | Este incidente ya está cerrado o cancelado, así que no se puede modificar. |
| `FORBIDDEN_ACTION` | 403 | No puede asignar a otros | No tienes permiso para hacer esta acción. Si crees que deberías tenerlo, habla con un administrador. |

## Dónde está en el código

- [`AssignIncident`](../../../backend/app/Modules/Incidents/Application/AssignIncident.php): tomar o asignar y transacción.
- [`IncidentAccess`](../../../backend/app/Modules/Incidents/Domain/IncidentAccess.php): reglas `Take` y `Assign`.
- [`EloquentAssigneeDirectory`](../../../backend/app/Modules/Incidents/Infrastructure/EloquentAssigneeDirectory.php): quién puede recibir incidentes.
- [`AssignDialog`](../../../frontend/src/modules/incidents/presentation/components/AssignDialog.vue) y [`useAssignees`](../../../frontend/src/modules/incidents/application/useAssignees.ts).
- Explicación del módulo: [Módulo Incidents](../../backend/incidents.md).
