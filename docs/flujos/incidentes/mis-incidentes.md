---
title: 'Mis incidentes: Kanban'
description: El tablero de quien atiende incidentes, cómo se carga y qué pasa al arrastrar una tarjeta a otro estado.
audience: tecnico
area: flujos
module: incidents
order: 10
---

# Mis incidentes: Kanban

"Mis incidentes" es el tablero de quien atiende (`incidents.view.assigned`): lo que tiene asignado y sigue en curso, más lo que cerró o canceló en los últimos 7 días. Tiene cuatro vistas sobre los mismos datos: Kanban, Lista, Calendario y Mi día. Este flujo sigue el Kanban, que es donde se cambia el estado arrastrando.

```flujo
carriles: [Persona, Navegador, API, Base de datos]
pasos:
  - id: abre
    carril: Persona
    tipo: inicio
    texto: Abre Mis incidentes
    detalle: 'Botón "Mis incidentes" junto a "Nuevo incidente". La vista elegida queda en `?vista=` y se recuerda en el navegador.'
    va: pide
  - id: pide
    carril: Navegador
    texto: Pide GET /incidents/mine
    va: permiso
  - id: permiso
    carril: API
    tipo: decision
    texto: ¿Tiene incidents.view.assigned?
    si: lee
    no: e403
  - id: e403
    carril: API
    tipo: error
    texto: Responde 403
  - id: lee
    carril: Base de datos
    texto: Trae hasta 500 asignados
    detalle: 'En curso y terminados de los últimos 7 días (por `updated_at`), con el índice del dashboard: unos 1,5 ms. Si hay más de 500, avisa con `truncated`. Cada tarjeta trae sus `next_statuses`.'
    va: arrastra
  - id: arrastra
    carril: Persona
    texto: Arrastra una tarjeta a otra columna
    detalle: 'En portátil y monitor. Al tomarla, se marcan las columnas a las que puede ir y se apagan las demás. En celular y tablet, y con teclado, se usa "Mover a…" en la tarjeta.'
    va: puede
  - id: puede
    carril: Navegador
    tipo: decision
    texto: ¿Ese destino está en sus next_statuses?
    detalle: Solo sirve para no ofrecer lo imposible; la regla la aplica la API.
    si: nota
    no: nada
  - id: nada
    carril: Navegador
    tipo: error
    texto: No se puede soltar ahí
  - id: nota
    carril: Navegador
    texto: Pide nota o confirmación si el destino lo exige
    detalle: Resuelto y Cancelado piden nota; los estados finales piden confirmación. Es el mismo diálogo del detalle.
    va: cambia
  - id: cambia
    carril: API
    texto: Cambia el estado como en el detalle
    detalle: 'PUT /incidents/{id}/status con la versión de la tarjeta. Mismas reglas, errores y avisos que el flujo "Cambiar de estado".'
    va: ok
  - id: ok
    carril: API
    tipo: decision
    texto: ¿Se guardó?
    si: mueve
    no: conflicto
  - id: conflicto
    carril: Navegador
    tipo: error
    texto: Muestra el error y recarga
  - id: mueve
    carril: Navegador
    tipo: fin
    texto: La tarjeta queda en su columna nueva
    detalle: Cuando llegan cambios de otras personas por tiempo real, el tablero se recarga tras 1,5 s, para juntar varios avisos seguidos en una sola consulta.
```

## Qué ve la persona en cada error

| Código | HTTP | Cuándo | Mensaje en pantalla |
|---|---|---|---|
| `FORBIDDEN_ACTION` | 403 | Su rol no atiende incidentes | No tienes permiso para hacer esta acción. Si crees que deberías tenerlo, habla con un administrador. |
| `VERSION_CONFLICT` | 409 | El incidente cambió desde que se cargó el tablero | Alguien más guardó cambios en este incidente mientras lo tenías abierto. Recarga para ver la versión actual antes de seguir. |
| `INVALID_STATUS_TRANSITION` | 409 | Otra persona ya lo movió | Ese cambio de estado no está permitido desde el estado actual. Recarga la página para ver las opciones disponibles. |
| `STATUS_NOTE_REQUIRED` | 422 | Resuelto o Cancelado sin nota | Para este cambio de estado hace falta una nota. Escribe brevemente qué pasó y vuelve a intentarlo. |

## Dónde está en el código

- [`ListMyIncidents`](../../../backend/app/Modules/Incidents/Application/ListMyIncidents.php) y [`EloquentMyIncidents`](../../../backend/app/Modules/Incidents/Infrastructure/EloquentMyIncidents.php).
- [`board.ts`](../../../frontend/src/modules/incidents/domain/board.ts) (`canMoveTo`, columnas) y [`useMyIncidents`](../../../frontend/src/modules/incidents/application/useMyIncidents.ts).
- [`KanbanBoard`](../../../frontend/src/modules/incidents/presentation/board/KanbanBoard.vue), [`KanbanCard`](../../../frontend/src/modules/incidents/presentation/board/KanbanCard.vue) y [`MyIncidentsPage`](../../../frontend/src/modules/incidents/presentation/MyIncidentsPage.vue).
- Explicación del módulo: [Módulo Incidents](../../backend/incidents.md).
