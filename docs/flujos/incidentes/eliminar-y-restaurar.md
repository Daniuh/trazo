---
title: Eliminar y restaurar
description: El borrado lógico de un incidente, quién lo puede ver después y cómo se devuelve, sin perder nunca su historial.
audience: tecnico
area: flujos
module: incidents
order: 5
---

# Eliminar y restaurar

Eliminar un incidente es un borrado lógico (`deleted_at`): desaparece del listado, del dashboard y del detalle para casi todos, pero la fila y su historial siguen ahí. Solo quien tiene `incidents.restore` puede verlo (filtro "Ver eliminados" del listado) y devolverlo.

```flujo
carriles: [Persona, Navegador, API, Base de datos, Cola y avisos]
pasos:
  - id: elimina
    carril: Persona
    tipo: inicio
    texto: Pulsa Eliminar y confirma
    detalle: El botón aparece solo si la API lo permite (`actions.delete`) y siempre pide confirmación.
    va: envia
  - id: envia
    carril: Navegador
    texto: Envía DELETE /incidents/{id}
    va: puede
  - id: puede
    carril: API
    tipo: decision
    texto: ¿Lo ve y tiene incidents.delete?
    si: borra
    no: eneg
  - id: eneg
    carril: API
    tipo: error
    texto: Responde 404 o 403
  - id: borra
    carril: Base de datos
    texto: Marca deleted_at y anota "deleted"
    detalle: En la misma transacción. Nada se borra de verdad; las FK del historial siguen válidas.
    va: avisa
  - id: avisa
    carril: Cola y avisos
    texto: Avisa del cambio
    detalle: 'Las pantallas abiertas de otras personas reciben `incident.changed`; al volver a consultar reciben 404 y lo quitan.'
    va: lista
  - id: lista
    carril: Navegador
    texto: Vuelve al listado
    va: busca
  - id: busca
    carril: Persona
    texto: Abre "Ver eliminados" y elige Restaurar
    detalle: 'Solo con `incidents.restore`. Pedir los eliminados sin ese permiso responde 403.'
    va: restaura
  - id: restaura
    carril: Navegador
    texto: Envía POST /incidents/{id}/restore
    va: puede2
  - id: puede2
    carril: API
    tipo: decision
    texto: ¿Tiene incidents.restore y está eliminado?
    si: devuelve
    no: eneg2
  - id: eneg2
    carril: API
    tipo: error
    texto: Responde 403 o 409
  - id: devuelve
    carril: Base de datos
    texto: Quita deleted_at y anota "restored"
    va: avisa2
  - id: avisa2
    carril: Cola y avisos
    texto: Avisa del cambio
    va: listo
  - id: listo
    carril: Navegador
    tipo: fin
    texto: El incidente vuelve con todo su historial
```

## Qué ve la persona en cada error

| Código | HTTP | Cuándo | Mensaje en pantalla |
|---|---|---|---|
| `RESOURCE_NOT_FOUND` | 404 | No existe, ya estaba eliminado o no lo puede ver | No encontramos lo que buscas. Puede que se haya eliminado o que no tengas acceso. |
| `FORBIDDEN_ACTION` | 403 | Sin permiso de eliminar o de restaurar | No tienes permiso para hacer esta acción. Si crees que deberías tenerlo, habla con un administrador. |
| `INCIDENT_NOT_DELETED` | 409 | Se intenta restaurar uno que no está eliminado | Este incidente no está eliminado, así que no hay nada que restaurar. |

## Dónde está en el código

- [`DeleteIncident`](../../../backend/app/Modules/Incidents/Application/DeleteIncident.php): `delete` y `restore`.
- [`IncidentAccess`](../../../backend/app/Modules/Incidents/Domain/IncidentAccess.php): un eliminado solo lo ve quien puede restaurarlo.
- [`IncidentGate`](../../../backend/app/Modules/Incidents/Application/IncidentGate.php): solo restaurar busca entre los eliminados.
- [`ListIncidents`](../../../backend/app/Modules/Incidents/Application/ListIncidents.php): "Ver eliminados" exige `incidents.restore`.
- Explicación del módulo: [Módulo Incidents](../../backend/incidents.md).
