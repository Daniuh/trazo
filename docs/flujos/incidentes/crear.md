---
title: Crear un incidente
description: Del formulario que guía al incidente guardado con su código del año, su vencimiento y su historial, protegido contra el doble envío.
audience: tecnico
area: flujos
module: incidents
order: 1
---

# Crear un incidente

Un mismo formulario sirve para crear y para editar. Al crear, ayuda a la persona: elige la prioridad en tarjetas con su plazo, sugiere el vencimiento, muestra incidentes parecidos ya reportados y una vista previa. La API valida todo de nuevo y es quien decide el código, el estado inicial y el vencimiento si no se indicó uno.

```flujo
carriles: [Persona, Navegador, API, Base de datos, Cola y avisos]
pasos:
  - id: llena
    carril: Persona
    tipo: inicio
    texto: Llena título, descripción y prioridad
    detalle: 'En `/incidentes/nuevo`. Si escribe un título, "¿Ya lo reportaron?" busca parecidos tras 400 ms. Puede elegir el vencimiento (Sugerido, Hoy, Mañana u Otra fecha) y, si tiene permiso, a quién asignarlo o "Asignármelo".'
    va: revisa
  - id: revisa
    carril: Navegador
    tipo: decision
    texto: ¿Los campos se ven bien?
    detalle: Validación solo de experiencia (título de 5 a 80 caracteres con contador, campos obligatorios). La regla de verdad está en la API.
    si: envia
    no: marca
  - id: marca
    carril: Navegador
    tipo: error
    texto: Marca los campos
  - id: envia
    carril: Navegador
    texto: Envía POST /incidents con Idempotency-Key
    detalle: 'La clave se genera por contenido: reintentar el mismo envío (doble clic, red lenta) reutiliza la clave; cambiar algo genera otra.'
    va: repetido
  - id: repetido
    carril: API
    tipo: decision
    texto: ¿Ya llegó esa clave con éxito?
    detalle: 'La clave es por persona y por ruta y se recuerda 24 h. Si llega con otro contenido, responde 409 `IDEMPOTENCY_KEY_REUSED`; si el primer envío sigue en curso, 409 `IDEMPOTENCY_IN_PROGRESS`.'
    si: repite
    no: valida
  - id: repite
    carril: API
    tipo: fin
    texto: Devuelve la misma respuesta, sin crear otro
    detalle: Con la cabecera `Idempotent-Replayed`.
  - id: valida
    carril: API
    tipo: decision
    texto: ¿Datos válidos y permiso para crear?
    detalle: 'Título de 5 a 80, descripción hasta 5.000, prioridad activa del catálogo, vencimiento en el futuro (si viene) y permiso `incidents.create`.'
    si: asignado
    no: e422
  - id: e422
    carril: API
    tipo: error
    texto: Responde 422 o 403
  - id: asignado
    carril: API
    tipo: decision
    texto: Si trae asignado, ¿lo puede asignar?
    detalle: 'A cualquiera con `incidents.assign`; solo a sí mismo con `incidents.take`. Quien no puede asignar a otros recibe siempre 403, para no revelar a quién se podría asignar.'
    si: vence
    no: e403
  - id: e403
    carril: API
    tipo: error
    texto: Responde 403 o 422
  - id: vence
    carril: API
    texto: Fija el vencimiento
    detalle: Si la persona lo indicó, se respeta. Si no, ahora + el tiempo de la prioridad (Baja 5 días, Media 3 días, Alta 1 día, Crítica 4 h; datos editables del catálogo).
    va: codigo
  - id: codigo
    carril: Base de datos
    texto: Toma el siguiente código del año
    detalle: 'Ejemplo `TZ-26-0142`: prefijo, año del negocio (America/Bogota) y consecutivo de `incident_code_sequences`, tomado en la misma transacción con una sola sentencia atómica.'
    va: guarda
  - id: guarda
    carril: Base de datos
    texto: Guarda el incidente y su historial
    detalle: 'En la misma transacción: el incidente en el estado inicial (Abierto), `created_by` obligatorio, y la entrada `created` del historial (con el asignado si lo hubo), con IP y navegador.'
    va: avisa
  - id: avisa
    carril: Cola y avisos
    texto: Avisa del cambio tras confirmar
    detalle: 'Solo después del commit: sube la generación de la caché del dashboard, emite `incident.changed` a los canales que pueden verlo y crea las notificaciones (al asignado; a coordinación si es crítico o queda sin asignar).'
    va: detalle
  - id: detalle
    carril: Navegador
    tipo: fin
    texto: Abre el detalle con "Incidente TZ-26-0142 creado"
```

## Qué ve la persona en cada error

| Código | HTTP | Cuándo | Mensaje en pantalla |
|---|---|---|---|
| `VALIDATION_FAILED` | 422 | Algún campo no cumple; cada campo trae su código (`TOO_SHORT`, `TOO_LONG`…) | Hay datos por corregir. Revisa los campos marcados. (y el mensaje de cada campo debajo de él) |
| `FORBIDDEN_ACTION` | 403 | Sin permiso para crear, o para asignar a esa persona | No tienes permiso para hacer esta acción. Si crees que deberías tenerlo, habla con un administrador. |
| `INVALID_ASSIGNEE` | 422 | Quien asigna eligió a alguien que no puede recibir incidentes | Esta persona no puede recibir incidentes. Elige a alguien de la lista. |
| `IDEMPOTENCY_IN_PROGRESS` | 409 | El mismo envío todavía se está procesando | Tu incidente se está creando. Espera unos segundos antes de intentarlo de nuevo. |
| `IDEMPOTENCY_KEY_REUSED` | 409 | La misma clave llegó con otro contenido | No pudimos confirmar el envío. Recarga la página e inténtalo de nuevo. |

## Dónde está en el código

- [`CreateIncident`](../../../backend/app/Modules/Incidents/Application/CreateIncident.php): permiso, asignado, vencimiento y transacción.
- [`CreateIncidentRequest`](../../../backend/app/Modules/Incidents/Http/Requests/CreateIncidentRequest.php) y [`IncidentRequest`](../../../backend/app/Modules/Incidents/Http/Requests/IncidentRequest.php): validación.
- [`EnsureIdempotency`](../../../backend/app/Modules/Shared/Http/Middleware/EnsureIdempotency.php): doble envío.
- [`IncidentCodes`](../../../backend/app/Modules/Incidents/Infrastructure/IncidentCodes.php) e [`IncidentRules`](../../../backend/app/Modules/Incidents/Domain/IncidentRules.php): código del año y vencimiento.
- [`EloquentIncidentRepository`](../../../backend/app/Modules/Incidents/Infrastructure/EloquentIncidentRepository.php): guarda y emite `IncidentChanged`.
- [`useIncidentForm`](../../../frontend/src/modules/incidents/application/useIncidentForm.ts) e [`IncidentFormPage`](../../../frontend/src/modules/incidents/presentation/IncidentFormPage.vue).
- Explicación del módulo: [Módulo Incidents](../../backend/incidents.md) e [Idempotencia](../../api/idempotencia.md).
