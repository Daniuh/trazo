---
title: Módulo de tiempo real (frontend)
description: Conexión con Reverb, bus de avisos y qué hace cada pantalla cuando otra persona cambia algo.
audience: tecnico
area: frontend
module: realtime
order: 8
---

# Módulo de tiempo real (frontend)

Mantiene una conexión con Reverb mientras hay sesión y avisa a las pantallas que algo cambió. **Los avisos no traen datos:** cada pantalla vuelve a consultar la API, que aplica los permisos ([Módulo Realtime](../backend/realtime.md)).

## Conexión

- **Cuándo.** La abre `useRealtimeConnection`, que vive en el shell. El shell solo existe con sesión, así que la conexión se abre al entrar y se cierra al salir. Arranca 1,5 s después de abrir la app, para no competir con lo que la pantalla necesita mostrar.
- **Cómo se conecta.**
  - Pide `GET /realtime` para conocer la clave pública y la ruta (`/ws`). Lo guarda en `sessionStorage`, así que se pide una vez por pestaña.
  - Se conecta con Laravel Echo al mismo dominio de la app, por el gateway, con `wss` si la página va por `https`.
  - Pusher pide una firma por canal, todas en el mismo momento: el autorizador las junta y las pide en **una sola** petición, con el cliente HTTP de la app, que lleva el token de la sesión.
- **Canales** (`channelsFor`): el personal siempre; `incidents.all` con `incidents.view.any`; `incidents.unassigned` con `incidents.view.unassigned`. Si los permisos cambian, entra a los nuevos y sale de los que ya no aplican.
- **Sin el aviso propio.** El cliente HTTP manda el `X-Socket-ID` de la pestaña (`shared/http/socketId.ts`) y la API no le avisa de sus propios cambios.
- **Reconexión.** Al volver la conexión publica `reconnected`, por si se perdió algún aviso.
- **Si el tiempo real falla,** la app sigue igual, sin avisos.

## Avisos y qué hace cada pantalla

Las pantallas escuchan el bus con `useRealtime(evento, función)`, que deja de escuchar sola al desmontar. No conocen Echo ni los canales.

| Pantalla | `incident` | `reconnected` |
|---|---|---|
| Listado de incidentes | Aviso "Hay N incidentes con cambios de otras personas · Actualizar" (cuenta incidentes, no avisos); no mueve las filas | Vuelve a consultar |
| Detalle y vista previa | Si es el mismo incidente y no hay una acción en curso, se vuelven a leer los datos y el historial | Vuelve a consultar (detalle) |
| Formulario de edición | Si es el mismo incidente: aviso "Otra persona cambió este incidente…" con "Recargar"; no toca lo que se escribió | — |
| Dashboard | Refresca las cifras, como máximo una vez cada 10 s y nunca en modo Personalizar | Igual |

Por el canal personal (`useRealtimeNotices`):

- **`assigned`:** pide el incidente a la API y muestra "Te asignaron INC-000123: título" con "Abrir". Es la opción `action` de `notify` en `useFeedback`, que deja el aviso 8 s en lugar de 4.
- **`session`:** vuelve a pedir la sesión (`session.refresh()`).
  - Si la sesión ya no vale, la API responde `UNAUTHENTICATED` y el cliente HTTP lleva al login con "Tu sesión terminó".
  - Si cambiaron los permisos, el menú y los botones se ajustan y los canales se actualizan.

## Capas

| Capa | Pieza | Qué hace |
|---|---|---|
| domain | `realtime.ts` | Tipos de aviso y a qué canales se suscribe cada persona |
| infrastructure | `echoClient.ts` | Configuración de conexión y creación de Echo con su autorizador |
| application | `useRealtimeConnection`, `realtimeBus` (`onRealtime`, `emitRealtime`, `useRealtime`) | Conexión, canales y bus de avisos |
| presentation | `useRealtimeNotices` | Aviso de asignación y cambios de sesión |

## Dónde está en el código

- [`frontend/src/modules/realtime/`](../../frontend/src/modules/realtime/)
- [`shared/http/socketId.ts`](../../frontend/src/shared/http/socketId.ts) y [`shared/http/client.ts`](../../frontend/src/shared/http/client.ts)
- Pantallas: [`IncidentListPage`](../../frontend/src/modules/incidents/presentation/IncidentListPage.vue), [`IncidentDetailPage`](../../frontend/src/modules/incidents/presentation/IncidentDetailPage.vue), [`IncidentFormPage`](../../frontend/src/modules/incidents/presentation/IncidentFormPage.vue) y [`useDashboard`](../../frontend/src/modules/dashboard/application/useDashboard.ts)
- Pruebas: [`realtime.spec.ts`](../../frontend/src/modules/realtime/realtime.spec.ts) y [`e2e/realtime.spec.ts`](../../frontend/e2e/realtime.spec.ts)
- Decisión: [ADR-0057](../decisiones/0057-tiempo-real-en-la-practica.md)
