---
title: "ADR-0078: Mis incidentes (Kanban, Lista, Calendario y Mi día)"
description: "Quien atiende incidentes tiene un tablero con lo que tiene asignado, en Kanban (arrastrando para cambiar de estado), Lista, Calendario o Mi día"
audience: tecnico
area: decisiones
tags: [producto]
order: 88
---

# ADR-0078: Mis incidentes (Kanban, Lista, Calendario y Mi día)

- **Estado:** Aceptada. Construida en dos bloques: Kanban y Lista; luego Calendario y Mi día.
- **Fecha:** 2026-10-31

## Contexto
Quien atiende incidentes trabajaba desde el listado general, filtrando por "Asignado a mí" cada vez, y para mover un incidente de estado tenía que abrirlo. Hacía falta un lugar propio para ver de un vistazo lo que tiene a su cargo y avanzarlo.

## Alternativas evaluadas
Para traer los datos:

- **Endpoint propio `GET /incidents/mine`** con todo el tablero de una vez y los estados a los que puede ir cada tarjeta.
- **El listado general** con `filter[assignee]` y páginas.

Para mover tarjetas en el Kanban:

- **Arrastrar** en portátil y monitor, y **"Mover a…"** en cada tarjeta (celular, tablet y teclado).
- **Solo mirar** y cambiar el estado desde el detalle.

Para arrastrar:

- **Arrastrar y soltar nativo del navegador.**
- **Una librería** (SortableJS o similar).

Qué incidentes entran:

- **Los asignados que siguen en curso, más los cerrados o cancelados de los últimos 7 días.**
- **Todo el histórico de asignados.**

## Decisión
- **Botón "Mis incidentes"** junto a "Nuevo incidente" en el listado, solo con `incidents.view.assigned` (Agente, Supervisor y Super admin). Lleva a `/incidentes/mios`.
- **`GET /incidents/mine`:** los asignados a quien pregunta, sin eliminados, en curso o terminados hace 7 días o menos, ordenados por vencimiento y con un tope de 500 (`truncated` avisa si quedaron fuera). Cada uno trae `actions` y `next_statuses`. Sin el permiso, 403.
- **Vistas** en un selector (`?vista=kanban|lista|calendario|dia`), que se recuerda en el navegador:
  - **Kanban:** una columna por estado, en el orden del catálogo.
    - En portátil y monitor se arrastra la tarjeta. Al tomarla, las columnas a las que puede ir se marcan con un borde punteado y las demás se apagan. Las 6 caben en monitor; en portátil la fila se desplaza de lado. Cada columna mide lo que su contenido y tiene un tope de alto (hasta el final de la pantalla) y sus tarjetas se desplazan por dentro, así una columna llena no alarga la página.
    - En celular y tablet se ve una columna a la vez, elegida en pestañas con su número.
    - "Mover a…" en todas las vistas. Mover usa el mismo diálogo del detalle: nota obligatoria en Resuelto y Cancelado, aviso en los estados finales y el mismo manejo del conflicto de versión.
  - **Lista:** la tabla del listado (o sus tarjetas), sin la columna "Asignado", con el orden aplicado en el navegador.
  - **Calendario:** cada incidente en el día en que vence, en la hora local de quien mira. En portátil y monitor, el mes de lunes a domingo con hasta 3 incidentes por día y "+N más", que abre el día en un panel. En celular y tablet, una agenda con solo los días que tienen algo. El mes va en la URL (`?mes=2026-10`).
  - **Mi día:** la fecha de hoy y cinco bloques para la reunión diaria: vencidos, vencen hoy, en progreso, en espera y resueltos ayer. Cada incidente va en un solo bloque, el primero que le toque. En portátil y monitor los cinco van en una fila, como las columnas del Kanban, cada uno con desplazamiento interno; en celular y tablet, uno debajo del otro con tope de alto.
- **Tiempo real:** cualquier aviso de incidente vuelve a pedir el tablero un momento después (junta varios avisos en una consulta).

## Alternativas descartadas
- **El listado con filtro:** pagina de a 20 y no trae `next_statuses`. El Kanban necesita todo a la vez, y saber a dónde puede ir cada tarjeta sin calcular reglas en el front.
- **Solo mirar:** obliga a abrir cada incidente para avanzarlo, que es justo lo que se quería evitar.
- **Librería de arrastre:** el arrastre nativo alcanza para soltar en una columna (no hace falta reordenar dentro de ella), no suma dependencias ni código que revisar, y en pantallas táctiles se usa "Mover a…", que además sirve con teclado.
- **Todo el histórico:** con los meses el tablero se llenaría de cerrados y dejaría de servir para el trabajo del día.

## Consecuencias
- Avanzar un incidente es arrastrar una tarjeta, sin abrirlo, con las mismas reglas y notas del detalle.
- Medido: la consulta usa el índice `dashboard_assignee` (`assigned_to` primero) y tarda 1,5 ms. Las consultas no crecen con la cantidad de tarjetas (prueba N+1).
- Calendario y Mi día salen del mismo tablero: no piden nada nuevo a la API. "Vencido" lo sigue calculando el back; el front solo agrupa por día.

## Dónde está en el código
- Back: [`ListMyIncidents.php`](../../backend/app/Modules/Incidents/Application/ListMyIncidents.php), [`EloquentMyIncidents.php`](../../backend/app/Modules/Incidents/Infrastructure/EloquentMyIncidents.php) e [`IncidentResource.php`](../../backend/app/Modules/Incidents/Http/IncidentResource.php) (`board`).
- Front: [`board.ts`](../../frontend/src/modules/incidents/domain/board.ts), [`useMyIncidents.ts`](../../frontend/src/modules/incidents/application/useMyIncidents.ts), [`useBoardView.ts`](../../frontend/src/modules/incidents/application/useBoardView.ts), [`MyIncidentsPage.vue`](../../frontend/src/modules/incidents/presentation/MyIncidentsPage.vue) y [`board/`](../../frontend/src/modules/incidents/presentation/board/).
- Pruebas: [`MyIncidentsTest.php`](../../backend/tests/Feature/Incidents/MyIncidentsTest.php), [`board.spec.ts`](../../frontend/src/modules/incidents/application/board.spec.ts) y la E2E [`my-incidents.spec.ts`](../../frontend/e2e/my-incidents.spec.ts).
