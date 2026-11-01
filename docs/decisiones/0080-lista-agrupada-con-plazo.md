---
title: "ADR-0080: Listado de incidentes como lista agrupada con el avance del plazo"
description: "El listado pasa a una lista de dos líneas agrupada por vencimiento, estado, prioridad o asignado, con una barra que muestra cuánto del plazo se ha gastado; la tabla queda como segunda vista"
audience: tecnico
area: decisiones
order: 90
---

# ADR-0080: Listado de incidentes como lista agrupada con el avance del plazo

- **Estado:** Aceptada. Cambia la presentación del [ADR-0045](0045-listado-en-el-front.md) y suma al [ADR-0044](0044-listado-de-incidentes.md) el agrupamiento y los totales por grupo.
- **Fecha:** 2026-11-01

## Contexto
El listado se presentaba como una tabla con columnas (título, prioridad, estado, asignado, vence) en portátil y monitor, y como tarjetas en tablet y celular. Es la forma de casi cualquier panel de administración. Además, la tabla solo decía si un incidente ya estaba vencido: no mostraba cuáles estaban a punto de vencerse, que es lo que más interesa a quien atiende.

## Alternativas evaluadas
- **Lista agrupada con barra de tiempo:** cada incidente en dos líneas, sin columnas, agrupados por un criterio que se puede cambiar y con una barra que muestra cuánto del plazo se ha gastado.
- **Vista dividida, como un correo:** la lista a la izquierda y el detalle siempre abierto a la derecha.
- **Línea de tiempo de vencimientos:** los incidentes ubicados sobre un eje de días.

Para la tabla actual:

- **Conservarla como segunda vista** ("Lista | Tabla").
- **Quitarla.**

Para los grupos, con la paginación en el servidor:

- **El back ordena primero por grupo** y envía el total de cada grupo con los filtros aplicados.
- **El front agrupa lo que llega** en cada página.

Para armar cada página agrupada en el back:

- **Ordenar todo lo filtrado por grupo** (`ORDER BY CASE …`) y cortar la página.
- **Pedir solo los grupos de la página:** contar cada grupo, ver en cuáles cae la página y consultar solo esos, cada uno con un filtro que usa los índices.

## Decisión
- **Lista por defecto, tabla como segunda vista.** "Lista | Tabla" va en la URL (`?vista=tabla`); sin el parámetro siempre se ve la lista.
- **Cada fila en dos líneas:** arriba, el código, el título y la prioridad; abajo, el estado, el asignado y la barra de tiempo con su texto ("quedan 5 h", "venció hace 3 h").
- **"Agrupar por"** (`?agrupar=`): vencimiento (por defecto), estado, prioridad, asignado o ninguno.
  - Vencimiento: Vencidos, Vencen hoy, Próximos 7 días, Más adelante y Terminados. "Hoy" es el día del negocio (`BUSINESS_TIMEZONE`).
  - Estado, en el orden del flujo; prioridad, de Crítica a Baja; asignado, primero "Sin asignar" y luego por nombre.
  - Cada grupo muestra su total real y se puede plegar.
- **El back calcula el plazo** y lo envía en cada incidente como `deadline`: el grupo de vencimiento (`bucket`), el porcentaje del plazo gastado (`time_used`, de 0 a 100, solo en los pendientes) y si se resolvió a tiempo (`resolved_on_time`, solo en los terminados con fecha de resolución). El front solo pinta.
- **Los terminados no llevan barra:** dicen "Resuelto a tiempo" o "Resuelto 2 días tarde". Los cancelados sin fecha de resolución dicen solo su estado.
- **Colores de la barra:** verde con holgura, naranja desde el 75 % del plazo y rojo al vencer, con los colores base de la marca sobre el fondo de la página.
- **El back arma la página por grupos** cuando llega `group_by` (`due`, `status`, `priority` o `assignee`, de una lista blanca): primero cuenta cada grupo con los filtros (una consulta que resuelve un índice, sin cruzar tablas), los pone en su orden, salta los que quedan antes de la página y de cada grupo que la toca pide solo los ids que caen en ella, con el orden elegido. Al final carga esos incidentes de una vez. Así cada grupo sale entero y seguido entre páginas. El total general es la suma de los grupos, y `meta.groups` trae el total de cada grupo que aparece en la página.
- **La exportación respeta el mismo orden**: lo que se ve es lo que se exporta. Como lee todas las filas igual, ahí sí se ordena todo por grupo.
- **Por vista:** en celular la prioridad queda como un punto y el asignado como sus iniciales; en monitor la lista va con la vista previa a la derecha, que ya existía.

## Alternativas descartadas
- **Vista dividida:** solo cambia algo en portátil y monitor, y se parece a la vista previa que ya había en monitor. Se conserva esa vista previa junto a la lista.
- **Línea de tiempo:** se lee mal con cientos de incidentes y no cabe en el celular.
- **Quitar la tabla:** a quien revisa muchas filas de una vez le sirve la grilla, y ya estaba construida y probada.
- **Agrupar en el front:** cada página mostraría solo sus propios conteos, y un grupo podría aparecer, desaparecer y volver entre páginas según el orden elegido.
- **Ordenar todo por grupo:** el orden por una expresión no aprovecha los índices, así que MySQL ordena todo lo filtrado para entregar 20 filas. Con 100.000 incidentes sin filtros tardaba de 130 a 300 ms; pidiendo solo los grupos de la página, de 22 a 42 ms.

## Consecuencias
- Cada página agrupada hace el conteo por grupo (que reemplaza al conteo general) y una consulta corta por cada grupo que toca la página: casi siempre uno o dos. Medido con `EXPLAIN` sobre 100.000 incidentes en [Rendimiento](../base-de-datos/rendimiento.md).
- En la lista, la tabla de portátil y monitor y las tarjetas de celular y tablet pasan a la segunda vista ("Tabla"); la lista de dos líneas sirve en las cuatro vistas.

## Dónde está en el código
- Back: `IncidentRules::deadline` y `DueBucket` en [`Domain`](../../backend/app/Modules/Incidents/Domain/), `IncidentGroup`, el conteo, el filtro y el orden de cada grupo en [`IncidentGroups`](../../backend/app/Modules/Incidents/Infrastructure/IncidentGroups.php), la página por grupos en [`EloquentIncidentList`](../../backend/app/Modules/Incidents/Infrastructure/EloquentIncidentList.php) y `deadline` en [`IncidentResource`](../../backend/app/Modules/Incidents/Http/IncidentResource.php).
- Front: `groupSections` en [`domain/grouping.ts`](../../frontend/src/modules/incidents/domain/grouping.ts), [`IncidentGroupedList.vue`](../../frontend/src/modules/incidents/presentation/list/IncidentGroupedList.vue) y [`DeadlineMeter.vue`](../../frontend/src/modules/incidents/presentation/list/DeadlineMeter.vue).
