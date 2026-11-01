---
title: Conversación del incidente
description: Escribir y leer mensajes en un incidente, borrar el propio durante 5 minutos y cómo llegan a los demás participantes.
audience: tecnico
area: flujos
module: incidents
order: 7
---

# Conversación del incidente

Cada incidente tiene un chat. Quien lo puede ver lo lee; quien tiene `incidents.comment` (todos los roles) escribe, también en incidentes terminados, porque un mensaje no cambia el estado. Los mensajes no se editan: el propio se puede borrar durante 5 minutos, para corregir un error sin cambiar lo que otros ya leyeron.

```flujo
carriles: [Persona, Navegador, API, Base de datos, Cola y avisos, Participantes]
pasos:
  - id: abre
    carril: Persona
    tipo: inicio
    texto: Abre el chat del incidente
    detalle: 'Botón "Chat (N)" en la ficha del detalle, o un mensaje del historial (abre el chat en ese mensaje). `?conversacion=1` también lo abre. Mientras está abierto, los avisos de mensajes de ese incidente se dan por leídos.'
    va: lee
  - id: lee
    carril: Navegador
    texto: Pide GET /incidents/{id}/comments
    detalle: 50 por página, del más reciente hacia atrás; "Ver anteriores" pide la página siguiente con `before`.
    va: escribe
  - id: escribe
    carril: Persona
    texto: Escribe y pulsa Enter
    detalle: Hasta 2.000 caracteres. Shift + Enter hace un salto de línea.
    va: envia
  - id: envia
    carril: Navegador
    texto: Envía POST /incidents/{id}/comments
    va: limite
  - id: limite
    carril: API
    tipo: decision
    texto: ¿Menos de 20 mensajes en el minuto?
    detalle: 'Límite `comments`: 20 por minuto por persona.'
    si: puede
    no: e429
  - id: e429
    carril: API
    tipo: error
    texto: Responde 429
  - id: puede
    carril: API
    tipo: decision
    texto: ¿Lo ve, no está eliminado y puede comentar?
    si: guarda
    no: eneg
  - id: eneg
    carril: API
    tipo: error
    texto: Responde 404 o 403
  - id: guarda
    carril: Base de datos
    texto: Guarda el mensaje
    detalle: En `incident_comments`, con FK al incidente y a quien escribe. El detalle cuenta los mensajes (`comments_count`).
    va: avisa
  - id: avisa
    carril: Cola y avisos
    texto: Emite incident.commented y crea avisos
    detalle: 'Tras confirmar. Recibe el aviso quien creó el incidente, el asignado y quienes ya escribieron, nunca quien escribió. Hay un solo aviso de mensajes sin leer por incidente y persona: si ya existe, sube su contador.'
    va: llega
  - id: llega
    carril: Participantes
    tipo: fin
    texto: El mensaje aparece en sus pantallas y en la campana
  - id: borra
    carril: Persona
    tipo: inicio
    texto: Borra un mensaje propio
    detalle: El botón solo aparece en los propios y durante 5 minutos (la API lo dice en `actions` de cada mensaje).
    va: borra2
  - id: borra2
    carril: API
    tipo: decision
    texto: ¿Es suyo, no está borrado y tiene menos de 5 min?
    si: marca
    no: eneg2
  - id: eneg2
    carril: API
    tipo: error
    texto: Responde 403 o 409
  - id: marca
    carril: Base de datos
    tipo: fin
    texto: Lo marca como borrado
    detalle: 'El texto se vacía y queda la fecha de borrado; un CHECK de la tabla exige que un mensaje tenga texto o esté borrado, nunca ambos.'
```

Borrar un mensaje (pasos 10 a 12) es un camino aparte, con su propio inicio en la segunda fila del carril Persona. El borrado también se avisa en tiempo real, para que desaparezca de las pantallas abiertas.

## Qué ve la persona en cada error

| Código | HTTP | Cuándo | Mensaje en pantalla |
|---|---|---|---|
| `TOO_MANY_REQUESTS` | 429 | Más de 20 mensajes en un minuto | Hiciste demasiados intentos seguidos. Espera {wait} y vuelve a intentarlo. |
| `COMMENT_NOT_OWN` | 403 | Se intenta borrar un mensaje de otra persona | Solo quien escribió un mensaje puede borrarlo. |
| `COMMENT_DELETE_EXPIRED` | 409 | Pasaron más de 5 minutos | Ya pasaron más de 5 minutos desde que escribiste este mensaje, así que no se puede borrar. Si hace falta, escribe una aclaración. |
| `COMMENT_ALREADY_DELETED` | 409 | El mensaje ya estaba borrado | Este mensaje ya estaba borrado. |
| `RESOURCE_NOT_FOUND` | 404 | No puede ver el incidente o está eliminado | No encontramos lo que buscas. Puede que se haya eliminado o que no tengas acceso. |

## Dónde está en el código

- [`IncidentConversation`](../../../backend/app/Modules/Incidents/Application/IncidentConversation.php) y [`CommentRules`](../../../backend/app/Modules/Incidents/Domain/CommentRules.php): leer, escribir y la ventana de 5 minutos.
- [`DatabaseIncidentComments`](../../../backend/app/Modules/Incidents/Infrastructure/DatabaseIncidentComments.php): guarda y emite `IncidentCommented`.
- [`CommentRequest`](../../../backend/app/Modules/Incidents/Http/Requests/CommentRequest.php) y el límite `comments` en [`IncidentServiceProvider`](../../../backend/app/Modules/Incidents/Infrastructure/IncidentServiceProvider.php).
- [`useConversation`](../../../frontend/src/modules/incidents/application/useConversation.ts) y el panel del detalle.
- Explicación del módulo: [Módulo Incidents](../../backend/incidents.md). Avisos: [Notificaciones a participantes](../avisos/notificaciones.md).
