---
title: Un cambio llega a quien puede verlo
description: El camino de un cambio de incidente desde la transacción hasta las pantallas abiertas de las demás personas, sin enviar datos por el canal.
audience: tecnico
area: flujos
module: realtime
order: 2
---

# Un cambio llega a quien puede verlo

Cada vez que un incidente cambia (creado, editado, asignado, cambio de estado, eliminado, reabierto), las pantallas abiertas de las personas que lo pueden ver se enteran en un instante. El mensaje solo dice "este incidente cambió y cómo"; los datos se piden a la API, que aplica los permisos de quien pregunta.

```flujo
carriles: [Quien cambia, API, Cola, Reverb, Quien mira]
pasos:
  - id: cambia
    carril: Quien cambia
    tipo: inicio
    texto: Guarda un cambio en un incidente
    detalle: 'Su pestaña envía `X-Socket-ID` en cada petición, para no avisarse a sí misma.'
    va: confirma
  - id: confirma
    carril: API
    texto: Confirma la transacción y emite IncidentChanged
    detalle: 'El evento sale solo después del commit (`ShouldDispatchAfterCommit`): si la transacción falla, nadie recibe un aviso falso. Lleva el ULID, el tipo de cambio, quien lo creó y el asignado antes y después.'
    va: canales
  - id: canales
    carril: API
    texto: Calcula los canales
    detalle: 'El general; el de sin asignar si lo estaba antes o lo está ahora; y el personal de quien lo creó, de quien lo tenía y de quien lo tiene.'
    va: encola
  - id: encola
    carril: Cola
    texto: Envía el mensaje por la cola
    detalle: '`incident.changed` con `{id, change}`, a todos menos a la pestaña que hizo el cambio (`toOthers`).'
    va: difunde
  - id: difunde
    carril: Reverb
    texto: Lo entrega a los suscritos
    va: asignado
  - id: asignado
    carril: API
    tipo: decision
    texto: ¿Quedó asignado a otra persona?
    detalle: Si quien cambió se lo tomó a sí mismo, no hay aviso extra.
    si: aviso
    no: pantalla
  - id: aviso
    carril: Quien mira
    texto: Recibe "Te asignaron TZ-26-0142"
    detalle: Por su canal personal (`incident.assigned`), con enlace al incidente. El título se pide a la API.
    va: pantalla
  - id: pantalla
    carril: Quien mira
    tipo: decision
    texto: ¿Qué pantalla tiene abierta?
    va:
      - { a: lista, etiqueta: Listado }
      - { a: detalle, etiqueta: Detalle }
      - { a: dashboard, etiqueta: Inicio }
  - id: lista
    carril: Quien mira
    tipo: fin
    texto: '"Hay N incidentes con cambios · Actualizar"'
    detalle: No mueve las filas mientras la persona lee; actualiza cuando ella quiere.
  - id: detalle
    carril: Quien mira
    tipo: fin
    texto: El detalle se actualiza solo
    detalle: Con su historial. Si ya no lo puede ver, la API responde 404 y la pantalla lo quita. Un formulario de edición abierto avisa sin borrar el borrador.
  - id: dashboard
    carril: Quien mira
    tipo: fin
    texto: Las cifras se refrescan
    detalle: Como máximo una vez cada 10 s, y nunca mientras se personaliza.
```

## Dónde está en el código

- [`EloquentIncidentRepository`](../../../backend/app/Modules/Incidents/Infrastructure/EloquentIncidentRepository.php) e [`IncidentChanged`](../../../backend/app/Modules/Incidents/Application/Events/IncidentChanged.php): el evento tras el commit.
- [`BroadcastIncidentChange`](../../../backend/app/Modules/Realtime/Infrastructure/BroadcastIncidentChange.php) y [`Channels`](../../../backend/app/Modules/Realtime/Domain/Channels.php) (`forIncident`).
- [`realtimeBus`](../../../frontend/src/modules/realtime/application/realtimeBus.ts) y [`useRealtimeNotices`](../../../frontend/src/modules/realtime/presentation/useRealtimeNotices.ts).
- [`socketId.ts`](../../../frontend/src/shared/http/socketId.ts): la cabecera `X-Socket-ID`.
- Explicación: [Módulo Realtime](../../backend/realtime.md).
