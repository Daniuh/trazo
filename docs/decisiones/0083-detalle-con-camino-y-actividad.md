---
title: "ADR-0083: Detalle del incidente con su camino, el siguiente paso y una sola actividad"
description: "El detalle muestra el camino de estados, la barra del plazo y un botón con el siguiente paso; historial y conversación se unen en una actividad con el campo para escribir"
audience: tecnico
area: decisiones
tags: [frontend]
order: 93
---

# ADR-0083: Detalle del incidente con su camino, el siguiente paso y una sola actividad

- **Estado:** Aceptada. Cambia la presentación del detalle y reemplaza el panel de conversación del [ADR-0076](0076-conversacion-del-incidente.md). La API no cambia. La actividad dentro de la página fue reemplazada por el [ADR-0084](0084-detalle-con-historial-y-panel-de-chat.md).
- **Fecha:** 2026-10-31

## Contexto
El detalle tenía el código y el título, una fila de botones ("Cambiar estado", "Conversación", "Reasignar", "Editar", "Eliminar"), la descripción y el historial a la izquierda y los datos a la derecha. Es la forma de casi cualquier detalle. No decía en qué punto del flujo estaba el incidente ni cuánto plazo quedaba, y la conversación vivía en un panel aparte del historial, así que para entender qué pasó había que mirar en dos lugares.

## Alternativas evaluadas
- **Dejarlo como estaba**, con mejor estilo.
- **Detalle con camino y actividad:**
  - Encabezado con el camino de estados, la barra del plazo y un botón con el siguiente paso.
  - Historial y conversación en una sola actividad, con el campo para escribir arriba y un filtro.
  - Ficha a la derecha con las personas, las fechas y las acciones de edición.

Para el siguiente paso:

- **El primer estado permitido en el orden del catálogo.** Desde En progreso propondría En espera.
- **Avanzar, prefiriendo cerrar el trabajo:** de los estados permitidos que van más adelante en el camino, primero los que no son pendientes (Resuelto, Cerrado); si no hay ninguno adelante, el primero permitido.

Para la conversación:

- **Mantener el panel** además de la actividad.
- **Quitarlo:** la conversación queda dentro de la actividad.

## Decisión
- **Detalle con camino y actividad.**
- **Camino** (`statusPath`): los estados del catálogo en su orden, sin las salidas (los finales a los que se llega directo desde el inicial, como Cancelado), con los anteriores marcados como hechos y el actual resaltado. Si el incidente salió del camino, se dice cuál fue la salida.
- **Siguiente paso** (`nextStep`): avanzar prefiriendo cerrar el trabajo, siempre entre los estados que la API permite a esta persona (`next_statuses`). Abierto → En progreso, En progreso → Resuelto, En espera → En progreso, Resuelto → Cerrado. Las salidas nunca son el siguiente paso. Los demás estados quedan en "Cambiar a…" del menú ⋯. Si el incidente terminó, el botón principal es "Reabrir" o "Solicitar reapertura", como antes.
- **Actividad** (`activityFeed`): historial y mensajes juntos, del más nuevo al más viejo, con el campo para escribir arriba (Enter envía) y el filtro "Todo · Mensajes · Cambios". La descripción va al final, como el primer evento. "Ver actividad anterior" trae lo que falte de los dos.
- **Sin panel de conversación.** Un aviso de mensajes (`?conversacion=1`) lleva al detalle, baja hasta la actividad y pone el foco en el campo para escribir. Con el detalle abierto, sus mensajes cuentan como leídos.
- **Ficha:** quién reportó y quién atiende (con sus iniciales y "Asignar", "Reasignar" o "Tomar"), las fechas y "Editar" y "Eliminar".

## Alternativas descartadas
- **Solo estilo:** el problema era la estructura.
- **Primer estado del catálogo:** proponía En espera a quien está trabajando un incidente, que no es lo habitual.
- **Mantener el panel:** dos lugares para lo mismo; la actividad ya muestra los mensajes con su campo para escribir.

## Consecuencias
- La vista previa del listado en monitor sigue con su forma: usa "Cambiar estado" y la ficha de datos de siempre.
- El detalle pide el historial y los mensajes al abrir; antes los mensajes se pedían al abrir el panel.

## Dónde está en el código
- Reglas: [`domain/workflow.ts`](../../frontend/src/modules/incidents/domain/workflow.ts) y [`domain/activityFeed.ts`](../../frontend/src/modules/incidents/domain/activityFeed.ts).
- Pantalla: [`IncidentDetailPage.vue`](../../frontend/src/modules/incidents/presentation/IncidentDetailPage.vue) y [`presentation/detail/`](../../frontend/src/modules/incidents/presentation/detail/).
