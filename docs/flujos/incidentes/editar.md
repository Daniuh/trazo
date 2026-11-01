---
title: Editar con bloqueo optimista
description: Quién puede editar un incidente, cómo se evita pisar los cambios de otra persona y qué ve quien edita cuando alguien más guarda primero.
audience: tecnico
area: flujos
module: incidents
order: 2
---

# Editar con bloqueo optimista

Editar cambia el título, la descripción, la prioridad y el vencimiento. El estado y el asignado tienen sus propios flujos. Cada incidente lleva un número de versión: quien guarda envía la versión que conocía, y si otra persona guardó entretanto, la API lo rechaza en lugar de pisar su cambio.

```flujo
carriles: [Persona, Navegador, API, Base de datos, Cola y avisos]
pasos:
  - id: abre
    carril: Persona
    tipo: inicio
    texto: Pulsa Editar en el detalle
    detalle: El botón solo aparece si la API dice que puede (`actions.update`). El navegador no calcula la regla.
    va: carga
  - id: carga
    carril: Navegador
    texto: Carga el incidente con su versión
    detalle: Mientras edita, si llega por tiempo real un cambio de otra persona, avisa "Otra persona cambió este incidente · Recargar" sin borrar el borrador. Si intenta salir con cambios sin guardar, pide confirmación.
    va: guarda
  - id: guarda
    carril: Navegador
    texto: Envía PUT /incidents/{id} con version
    detalle: El vencimiento solo se envía si cambió; así un incidente vencido se puede seguir editando sin que la regla "no en el pasado" lo impida.
    va: puede
  - id: puede
    carril: API
    tipo: decision
    texto: ¿Lo ve y puede editarlo?
    detalle: 'Con `incidents.update.any`, mientras no esté cerrado ni cancelado. Con `incidents.update.own`, solo lo que creó y mientras siga en el estado inicial. Si no lo puede ver, 404, como si no existiera.'
    si: version
    no: eneg
  - id: eneg
    carril: API
    tipo: error
    texto: Responde 404, 403 o 409
  - id: version
    carril: API
    tipo: decision
    texto: ¿La versión es la actual?
    si: cambios
    no: e409
  - id: e409
    carril: API
    tipo: error
    texto: Responde 409 VERSION_CONFLICT
  - id: cambios
    carril: API
    tipo: decision
    texto: ¿Cambió algo?
    detalle: Compara campo por campo. Si nada cambió, devuelve el incidente tal cual, sin escribir ni dejar historial.
    si: actualiza
    no: igual
  - id: igual
    carril: API
    tipo: fin
    texto: Devuelve el incidente sin cambios
  - id: actualiza
    carril: Base de datos
    texto: UPDATE con la versión en el WHERE
    detalle: '`UPDATE … WHERE id = ? AND lock_version = ?` y la versión sube en la misma sentencia. Si otra transacción ganó en ese instante, no afecta filas y la API responde el mismo 409. En la misma transacción queda la entrada `updated` del historial con solo los campos que cambiaron (antes y después).'
    va: avisa
  - id: avisa
    carril: Cola y avisos
    texto: Avisa del cambio tras confirmar
    detalle: Caché del dashboard, `incident.changed` a quienes pueden verlo y notificaciones a los participantes.
    va: listo
  - id: listo
    carril: Navegador
    tipo: fin
    texto: Vuelve al detalle con "Cambios guardados."
```

Ante el `VERSION_CONFLICT`, el formulario conserva lo escrito, muestra el aviso y un botón "Recargar" que trae la versión actual.

## Qué ve la persona en cada error

| Código | HTTP | Cuándo | Mensaje en pantalla |
|---|---|---|---|
| `VERSION_CONFLICT` | 409 | Otra persona guardó después de que se abrió el formulario | Alguien más guardó cambios en este incidente mientras lo tenías abierto. Recarga para ver la versión actual antes de seguir. |
| `INCIDENT_EDIT_LOCKED` | 409 | Quien lo creó intenta editar cuando ya no está en el estado inicial | Este incidente ya lo están atendiendo, así que ya no puedes editarlo. Si necesitas cambiar algo, habla con un supervisor. |
| `INCIDENT_FINALIZED` | 409 | Está cerrado o cancelado | Este incidente ya está cerrado o cancelado, así que no se puede modificar. |
| `FORBIDDEN_ACTION` | 403 | Lo ve, pero su rol no incluye editarlo | No tienes permiso para hacer esta acción. Si crees que deberías tenerlo, habla con un administrador. |
| `RESOURCE_NOT_FOUND` | 404 | No existe, está eliminado o no lo puede ver | No encontramos lo que buscas. Puede que se haya eliminado o que no tengas acceso. |

## Dónde está en el código

- [`UpdateIncident`](../../../backend/app/Modules/Incidents/Application/UpdateIncident.php): comparación campo por campo y transacción.
- [`IncidentGate`](../../../backend/app/Modules/Incidents/Application/IncidentGate.php) e [`IncidentAccess`](../../../backend/app/Modules/Incidents/Domain/IncidentAccess.php): quién puede y por qué no.
- [`EloquentIncidentRepository`](../../../backend/app/Modules/Incidents/Infrastructure/EloquentIncidentRepository.php): la versión en el mismo `UPDATE`.
- [`UpdateIncidentRequest`](../../../backend/app/Modules/Incidents/Http/Requests/UpdateIncidentRequest.php): `version` obligatoria y `assignee` rechazado.
- [`useIncidentForm`](../../../frontend/src/modules/incidents/application/useIncidentForm.ts) e [`IncidentFormPage`](../../../frontend/src/modules/incidents/presentation/IncidentFormPage.vue): conflicto, "Recargar" y aviso de tiempo real.
- Explicación del módulo: [Módulo Incidents](../../backend/incidents.md).
