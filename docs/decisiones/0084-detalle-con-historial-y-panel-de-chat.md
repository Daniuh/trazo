---
title: "ADR-0084: Detalle con historial reciente, ficha a la par y un panel de chat y actividad"
description: "La actividad sale de la página: el detalle muestra las 5 entradas más recientes junto a la ficha, y el chat y toda la actividad viven en un panel con pestañas"
audience: tecnico
area: decisiones
order: 94
---

# ADR-0084: Detalle con historial reciente, ficha a la par y un panel de chat y actividad

- **Estado:** Aceptada. Reemplaza la actividad dentro de la página del [ADR-0083](0083-detalle-con-camino-y-actividad.md); el encabezado con el camino, el plazo y el siguiente paso se conserva. La API no cambia.
- **Fecha:** 2026-10-31

## Contexto
Con el ADR-0083, el detalle mostraba toda la actividad cargada (hasta 50 cambios y 50 mensajes) con el campo para escribir arriba. En un incidente con mucho movimiento la página se alargaba sin fin, y la ficha quedaba sola en una columna angosta a la derecha. Escribir y leer el historial competían por el mismo espacio. La descripción iba al final de la actividad, justo donde menos se ve.

En la revisión visual se pidió:

- Dejar arriba la información del incidente tal como está.
- Abajo, el historial y la ficha mitad y mitad.
- Que el chat viva en un panel, abierto desde un botón junto a "Editar", como lo usan otras herramientas del equipo.
- Que las iniciales de la ficha no se vean apretadas.

## Alternativas evaluadas
Para el largo de la actividad:

- Mostrarla toda, como antes.
- Un alto fijo con desplazamiento dentro de la página.
- Las entradas más recientes en la página y el resto en un panel.

Para el chat:

- Escribir en la página, como antes.
- Escribir solo en un panel.

Para la descripción:

- Al final del historial, como antes.
- En la ficha.
- Bajo el título, en una línea con "Ver más".

## Decisión
- **Encabezado igual**, con la descripción bajo el título en una sola línea. Si no cabe, "Ver más" despliega debajo la descripción completa (`IncidentDescription`).
- **Abajo, mitad y mitad** en portátil y monitor: el historial a la izquierda y la ficha a la derecha. En celular y tablet, una columna con la ficha primero, porque a la mitad de una tablet el historial queda demasiado angosto.
- **Historial con las 5 entradas más recientes** (`RECENT_ACTIVITY`), mensajes y cambios juntos, y "Ver toda la actividad (N)".
- **Panel de chat y actividad** (`IncidentActivityPanel`, sobre `AppSheet`) con dos pestañas:
  - **Chat:** en orden de conversación, lo nuevo abajo, con el campo para escribir al pie. Es el único lugar donde se escribe.
  - **Actividad:** todo el historial, lo nuevo arriba, con las notas completas.
- **Cada entrada lleva a su lugar:**
  - Un mensaje del historial abre el chat en ese mensaje, resaltado.
  - Una nota que no cabe abre la actividad en esa entrada, con la nota completa.
  - En la actividad, un mensaje lleva al chat.
- **"Chat (N)"** en la ficha, a la izquierda de "Editar". Se ve siempre, porque leer no exige permiso. Un aviso de mensajes (`?conversacion=1`) abre el panel en Chat con el foco en el campo.
- **Iniciales en tres tamaños** (`AppAvatar size`): 24 px en las filas del listado, 32 px en el historial y el chat, y 40 px en la ficha.

## Alternativas descartadas
- **Mostrar toda la actividad:** es lo que alargaba la página.
- **Alto fijo con desplazamiento interno:** dos barras de desplazamiento anidadas, incómodas en celular, donde además es fácil quedar atrapado en la caja al deslizar.
- **Escribir en la página y en el panel:** dos lugares para lo mismo. Además, el campo en la página empujaba el historial hacia abajo.
- **Descripción en la ficha:** una descripción larga descuadra la mitad y mitad, y es lo primero que alguien necesita leer, así que va junto al título.
- **Descripción al final del historial:** con solo 5 entradas casi nunca se vería.

## Consecuencias
- La página del detalle tiene un largo acotado sin importar cuánto movimiento tenga el incidente.
- La conversación vuelve a un panel, como en el ADR-0076, pero ahora con el historial completo en otra pestaña y entrando desde cualquier entrada del historial.
- `HistoryNote` ya no abre su propio diálogo: avisa (`more`) y el detalle abre la actividad. Con `full`, muestra la nota entera.
- `AppSheet` suma una barra opcional bajo el título (`toolbar`, para pestañas o filtros que no se desplazan) y avisa cuando terminó de abrirse (`shown`), para mover el foco sin que el diálogo lo recupere.
- La vista previa lateral del listado (monitor) no cambia y no lleva botón de chat: para conversar se entra al detalle. Así el chat tiene un solo lugar, y la vista previa sigue siendo un vistazo rápido para recorrer el listado, no un segundo detalle.

## Dónde está en el código
- [`IncidentDetailPage.vue`](../../frontend/src/modules/incidents/presentation/IncidentDetailPage.vue): distribución, apertura del panel y `?conversacion=1`.
- [`detail/IncidentDescription.vue`](../../frontend/src/modules/incidents/presentation/detail/IncidentDescription.vue), [`detail/IncidentHistory.vue`](../../frontend/src/modules/incidents/presentation/detail/IncidentHistory.vue) y [`detail/ActivityEntry.vue`](../../frontend/src/modules/incidents/presentation/detail/ActivityEntry.vue).
- [`detail/IncidentActivityPanel.vue`](../../frontend/src/modules/incidents/presentation/detail/IncidentActivityPanel.vue), [`detail/ChatMessage.vue`](../../frontend/src/modules/incidents/presentation/detail/ChatMessage.vue) y [`detail/ChatComposer.vue`](../../frontend/src/modules/incidents/presentation/detail/ChatComposer.vue).
- [`detail/IncidentProfile.vue`](../../frontend/src/modules/incidents/presentation/detail/IncidentProfile.vue): "Chat (N)".
- [`domain/activityFeed.ts`](../../frontend/src/modules/incidents/domain/activityFeed.ts): `RECENT_ACTIVITY` y `ActivityPanelTab`.
- [`shared/ui/AppAvatar.vue`](../../frontend/src/shared/ui/AppAvatar.vue) y [`shared/ui/AppSheet.vue`](../../frontend/src/shared/ui/AppSheet.vue).
- Pruebas E2E: [`detail.spec.ts`](../../frontend/e2e/detail.spec.ts), [`conversation.spec.ts`](../../frontend/e2e/conversation.spec.ts) y [`notifications.spec.ts`](../../frontend/e2e/notifications.spec.ts).
