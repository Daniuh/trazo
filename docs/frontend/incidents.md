---
title: Módulo de incidentes (frontend)
description: Listado con filtros en la URL, formulario de crear y editar, detalle con acciones, nota al cambiar de estado, reapertura e historial, y cómo se manejan los conflictos.
audience: tecnico
area: frontend
module: incidents
order: 4
---

# Módulo de incidentes (frontend)

Pantallas para crear, ver, editar y trabajar un incidente. **El front no decide nada:**

- Los botones salen del campo `actions` que envía la API.
- Los estados posibles salen de `next_statuses`.
- "Vencido" llega calculado (`is_overdue`).

Las reglas están en [Módulo Incidents](../backend/incidents.md).

## Rutas

| Ruta | Pantalla |
|---|---|
| `/incidentes` | Listado (menú "Incidentes"). El botón "Nuevo incidente" aparece solo con `incidents.create` |
| `/incidentes/nuevo` | Formulario de creación |
| `/incidentes/mios` | "Mis incidentes" (botón junto a "Nuevo incidente", solo con `incidents.view.assigned`). `?vista=kanban\|lista\|calendario\|dia` y, en el calendario, `?mes=2026-10` |
| `/incidentes/:id` | Detalle |
| `/incidentes/:id/editar` | El mismo formulario, para editar |

## Listado

Cada persona ve solo lo que la API le deja ver (ver [Módulo Incidents](../backend/incidents.md)). El front no filtra por su cuenta.

Por defecto se ve como **lista agrupada**; "Tabla" muestra la presentación anterior ([ADR-0080](../decisiones/0080-lista-agrupada-con-plazo.md)). Sobre los resultados, una barra de una sola fila dice cuántos hay y tiene "Agrupar por" y "Ordenar por" (este solo en portátil y monitor; en celular y tablet está en la barra de filtros), con el nombre al lado de cada selector (`AppSelect inline`), y "Lista | Tabla". La fila tiene alto fijo: al pasar a Tabla los dos selectores se van y lo de abajo no se mueve.

**Lista agrupada (`IncidentGroupedList`).** Cada incidente en dos líneas (`IncidentLine`): arriba el código, el título y la prioridad; abajo el estado, "Reapertura pendiente" si la hay, el asignado con sus iniciales y el avance del plazo (`DeadlineMeter`).

- **Agrupar por:** vencimiento (por defecto: Vencidos, Vencen hoy, Próximos 7 días, Más adelante y Terminados), estado, prioridad, asignado o ninguno. Cada grupo muestra su total con los filtros (lo manda la API) y se pliega tocando su nombre. Al cambiar de criterio, todos se abren.
- **Avance del plazo:** una barra con el porcentaje del plazo gastado y su texto: "Quedan 5 h", "Venció hace 3 h". Verde con holgura, naranja desde el 75 % y roja al vencer (`$deadline-fills`). Los terminados no llevan barra: "Resuelto a tiempo" (verde) o "Resuelto 2 días tarde" (rojo). Los datos los calcula la API (`deadline`); el front solo arma el texto (`domain/deadline.ts`).
- **Celular:** la prioridad queda como punto y el asignado como sus iniciales; los nombres siguen disponibles para lectores de pantalla.

| Vista | Lista | Tabla |
|---|---|---|
| Monitor | Elegir una fila abre a la derecha una **vista previa** con lo esencial, "Cambiar estado" y "Abrir detalle", sin salir del listado | Igual, con la vista previa; con ella abierta se oculta la columna "Creado" |
| Portátil | Elegir una fila abre el detalle | Tabla; elegir una fila abre el detalle |
| Tablet y celular | Elegir una fila abre el detalle | Tarjetas con código, título, estado, prioridad, asignado y vencimiento |

**Barra de filtros**

- **Portátil y monitor:** búsqueda; estado, prioridad y asignado como selectores; y el menú "Más filtros" con los rangos de creación y de vencimiento, "Solo vencidos", "Creados por mí", "Con reapertura pendiente" y "Ver eliminados". El orden se cambia tocando el encabezado de la tabla.
- **Celular y tablet:** búsqueda, "Filtros (n)" y "Ordenar por". "Filtros" abre un panel inferior con los mismos filtros (estado, prioridad y asignado como chips). El panel se edita en borrador y el botón dice cuántos resultados daría antes de aplicarlo.
- **Filtros activos:** debajo de la barra, como etiquetas que se quitan con ✕, más "Limpiar filtros". Una etiqueta aparece cuando ya se conoce el nombre de su valor, para no mostrar códigos internos mientras cargan los catálogos.
- **Búsqueda:** se consulta al dejar de escribir. Con menos de 3 letras se avisa ("Escribe al menos 3 letras para buscar.") y no se consulta. Busca en el código, con su año (`TZ-26-0142` o `26-142`), el título y la descripción.

**Qué opciones ve cada persona**

| Filtro | Quién lo ve |
|---|---|
| Asignado: todas las personas y "Sin asignar" | Quien puede asignar (`incidents.assign`) |
| Asignado: "Asignados a mí" y "Sin asignar" | Un agente, según lo que puede ver |
| Asignado | Un solicitante no lo ve: solo ve lo que creó |
| Ver eliminados | Quien puede restaurar (`incidents.restore`). En esa vista, cada fila ofrece "Restaurar" |

**Filtros en la URL.** Todo el criterio vive en la URL (`?status=open,in_progress&priority=critical&page=2`), también la vista y el grupo, en español: `?vista=tabla` y `?agrupar=estado|prioridad|asignado|ninguno` (sin ellos, lista por vencimiento), así que un enlace compartido o una recarga muestran lo mismo. Lo que no se reconoce se ignora y vuelve al valor por defecto. Cambiar un filtro vuelve a la página 1.

**Exportar.** Solo con `incidents.export`. Descarga en CSV exactamente lo que se ve filtrado, en el mismo orden ([Exportación](../backend/incidents.md#exportación)).

- **Dónde:** en monitor, el botón "Exportar" al final de la barra. En portátil, el mismo botón solo con el ícono (con texto, la barra no cabe en una fila); su nombre accesible es "Exportar" y al pasar el cursor dice "Exportar CSV". En celular y tablet, el menú ⋯ de la barra ("Más opciones del listado") con "Exportar CSV".
- **Cómo:** pide confirmación con el número de incidentes ("Se exportarán 1.234 incidentes…"). Mientras descarga, el botón muestra "Exportando…" y queda bloqueado. Al terminar avisa "Exportación lista. Revisa la carpeta de descargas.". Sin resultados, la opción queda desactivada.
- **Errores:** si pasa del tope, el mensaje dice cuántos son y cuál es el máximo y pide acotar los filtros. Si se exporta más de 5 veces en un minuto, dice cuánto esperar.

**Paginación.** "1–20 de 534", páginas con saltos (…) y 20, 50 o 100 por página. En celular, solo "anterior", "Página 3 de 27" y "siguiente".

**Estados.** Esqueleto mientras carga la primera vez. Dos vacíos distintos: sin resultados para los filtros (con "Limpiar filtros") o sin incidentes todavía (con "Nuevo incidente", si se puede crear). Error con "Reintentar". Si llega una respuesta vieja después de una nueva, se descarta.

## Mis incidentes

Lo que la persona tiene asignado, en curso o terminado hace 7 días o menos ([ADR-0078](../decisiones/0078-mis-incidentes.md)). Arriba: "‹ Incidentes", el título con un ícono de información que explica qué entra, y el selector **Kanban · Lista · Calendario · Mi día**. La vista va en la URL y se recuerda en el navegador (`useBoardView`, `localStorage`).

- **Kanban** (`KanbanBoard`, `KanbanCard`): una columna por estado, en el orden del catálogo, con su número. Cada tarjeta muestra código, título (dos líneas), prioridad, "Vencido" si aplica y el vencimiento con un ícono de calendario.
  - **Portátil y monitor:** las columnas lado a lado (mínimo 12rem, `$board-column-width`; las 6 caben en monitor y en portátil la fila se desplaza de lado). Cada columna mide lo que su contenido (una vacía no se estira al alto de la más llena) y llega como máximo al final del tablero; sus tarjetas se desplazan por dentro, con el nombre y el número siempre a la vista. El tablero ocupa justo el alto disponible (pantalla menos el margen de arriba y el espacio del dock, `$dock-clearance`), así la página no tiene barra propia: solo se desplazan las columnas que no caben. Cada tarjeta es `position: relative` para que su texto oculto ("Vence") no se escape del desplazamiento de la columna y alargue la página. La tarjeta se arrastra: al tomarla, las columnas a las que puede ir (`canMoveTo`, según `nextStatuses` de la API) tienen borde punteado azul y las demás se apagan.
  - **Celular y tablet:** una columna a la vez, elegida en pestañas "Estado (n)".
  - **"Mover a…"** (menú ⋯ de la tarjeta) en todas las vistas, también con teclado.
  - Mover abre el mismo diálogo del detalle (`useStatusChange` + `IncidentNoteDialog`). Si otra persona lo cambió antes, aparece el aviso de conflicto con "Recargar".
- **Lista** (`MyIncidentsList`): la tabla del listado sin la columna "Asignado" (portátil y monitor) o sus tarjetas (celular y tablet). Por vencimiento al abrir; el orden se cambia en el navegador (`sortBoardItems`), sin volver a pedir nada.
- **Calendario** (`MyCalendar`, `CalendarMonthGrid`, `useCalendarMonth`, `domain/calendar.ts`): cada incidente en el día en que vence, en la hora local. Arriba, el mes con "‹ ›" y "Hoy"; el mes va en la URL (`?mes=`).
  - **Portátil y monitor:** una tabla de lunes a domingo (`monthGrid`, semanas completas; los días de los meses vecinos, apagados). Cada día muestra hasta 3 incidentes (punto de prioridad, código y título, ordenados por hora) y "+N más" abre el día completo en un `AppSheet`. Hoy lleva el número en un círculo azul. Alto de cada día: `$calendar-cell-min-height`.
  - **Celular y tablet:** la agenda (`agendaDays`), solo los días del mes con incidentes, cada uno con sus filas (`IncidentRow` con la hora).
- **Mi día** (`MyDay`, `domain/myDay.ts`): "Hoy · jueves, 29 de octubre" y cinco bloques con su número: vencidos, vencen hoy, en progreso, en espera y resueltos ayer. Cada incidente va en un solo bloque, el primero que le toque (`daySections`); "Vencido" viene de la API. Cada bloque tiene tope de alto y sus incidentes se desplazan por dentro, con el nombre y el número siempre a la vista. En celular y tablet van uno debajo del otro (tope `$day-section-max-height`); en portátil y monitor, los cinco en una fila como el Kanban: mínimo `$board-column-width` cada uno (en portátil la fila se desplaza de lado), cada bloque del alto de su contenido y como máximo hasta el final de la pantalla, sin barra en la página (`.my-incidents--fill`).
- **`IncidentRow`:** la fila compacta que comparten la agenda, el día completo y Mi día: código, título (lleva al detalle), estado, "Vencido", prioridad y vencimiento.
- **Tiempo real:** cualquier aviso de incidente vuelve a pedir el tablero 1,5 s después (junta varios avisos); al reconectarse, también.
- **Estados:** esqueleto, error con "Reintentar", vacío ("No tienes incidentes asignados", con "Ver incidentes") y aviso si quedaron más de 500 fuera.

## Formulario (crear y editar)

| Vista | Forma |
|---|---|
El formulario guía mientras se escribe ([ADR-0082](../decisiones/0082-formulario-guiado.md)).

| Vista | Forma |
|---|---|
| Portátil y monitor | Dos columnas: a la izquierda título, "¿Ya lo reportaron?", descripción, prioridad y vencimiento; a la derecha, "Así se verá" y la asignación. Botones al final, a la derecha |
| Celular y tablet | Una columna, con la asignación y luego "Así se verá" plegado. "Cancelar" y "Crear incidente" quedan fijos abajo, sobre el dock |

- **Validación visual:** campos obligatorios y largo máximo. La API valida todo de nuevo, y sus errores aparecen junto a cada campo.
- **Prioridad en tarjetas** (`form/PriorityPicker`): radios reales con el punto de color, el tiempo de resolución del catálogo ("4 h para resolver", `formatResolution`) y cuándo usarla (`PRIORITY_HINTS`, fijas por código). Celular: de a dos; desde tablet, las cuatro en fila.
- **Vencimiento con atajos** (`form/DuePicker`, sobre `AppSegmented`), en la hora local, que viaja a la API en UTC:
  - **Sugerido** (solo al crear): deja el campo vacío para que lo calcule la API con la prioridad. Debajo, la fecha exacta y "en 1 día"; cambia en vivo con la prioridad.
  - **Hoy** y **Mañana**: a las 6:00 p. m. (`END_OF_DAY_HOUR`). "Hoy" no aparece después de esa hora.
  - **Otra fecha**: abre `AppDateTimeField`. Al editar, el atajo de un vencimiento guardado es "Otra fecha", y solo se envía si cambió.
- **"¿Ya lo reportaron?"** (`form/SimilarIncidents`, `useSimilarIncidents`, solo al crear): al dejar de escribir el título (400 ms), busca en el listado, con la visibilidad de cada persona, con las dos palabras más largas y, si no hay nada, con la más larga sola (`similarQueries`). Muestra hasta 3 con su estado y el total; cada uno se abre en otra pestaña para no perder lo escrito. Si la búsqueda falla, no muestra nada.
- **"Así se verá"** (`form/IncidentFormPreview`): la misma fila de la lista agrupada (`IncidentLine`) con lo escrito, la prioridad, el estado inicial (o el actual al editar), el asignado y el plazo. Es de muestra: `inert` y oculta para lectores de pantalla.
- **"Asignar a"** aparece solo al crear y solo para quien tiene `incidents.assign`. La lista viene de `/incidents/assignees`.
- **"Asignármelo"** es una casilla opcional, solo al crear, para quien tiene `incidents.take` sin `incidents.assign` (el agente). Marcada, envía el propio id como `assignee` (`selfAssignId` en `IncidentForm`); el historial lo muestra como "creó el incidente y se lo asignó" ([ADR-0072](../decisiones/0072-asignarse-al-crear.md)).
- **Idempotencia:** cada envío de creación lleva una `Idempotency-Key`. Si se reintenta el mismo envío, viaja la misma clave; si los datos cambiaron, una nueva. Mientras se guarda, el botón queda bloqueado.
- **Si el incidente ya no se puede editar** (porque otra persona lo cerró, por ejemplo), abrir la edición lleva al detalle.

## Detalle

El detalle muestra en qué punto va el incidente, con lo reciente a la vista y el chat y toda la actividad en un panel aparte ([ADR-0083](../decisiones/0083-detalle-con-camino-y-actividad.md), [ADR-0084](../decisiones/0084-detalle-con-historial-y-panel-de-chat.md)).

| Vista | Forma |
|---|---|
| Portátil y monitor | Encabezado (`detail/IncidentHeader`) a todo el ancho: código, estado, "Vencido" y prioridad; el título con la descripción en una línea; el camino del incidente; y en una fila el plazo con su barra y el siguiente paso con el menú ⋯. Debajo, mitad y mitad: el historial a la izquierda y la ficha a la derecha |
| Celular y tablet | Una columna: encabezado (el camino en columna en celular y el siguiente paso a lo ancho), la ficha y el historial |

- **Descripción** (`detail/IncidentDescription`): bajo el título, en una sola línea que termina en "…". Si no cabe, "Ver más" despliega debajo la descripción completa (`aria-expanded`) y "Ver menos" la recoge.
- **Camino** (`detail/StatusPath`, `statusPath`): los estados del catálogo en orden, sin las salidas (Cancelado), con los anteriores hechos y el actual resaltado (`aria-current="step"`). Si el incidente salió del camino, dice "Salió del camino: Cancelado".
- **Plazo** (`detail/DeadlineBar`): "Venció hace 2 días", "Quedan 5 h" o "Resuelto a tiempo", la fecha de vencimiento y la barra a lo ancho (solo en los pendientes), con el mismo cálculo de la lista.
- **Ficha** (`detail/IncidentProfile`): quién reportó y quién atiende, con sus iniciales en grande (`AppAvatar size="lg"`, 40 px); "Asignar", "Reasignar" o "Tomar" junto a quien atiende; las fechas; y al pie "Chat (N)", "Editar" y "Eliminar". "Chat" se ve siempre: leer los mensajes no exige permiso, escribir sí.

- **Volver:** arriba del título va "‹ Incidentes" (`AppBackLink`), que regresa al listado tal como se dejó: filtros, orden y página (`listReturn.ts` recuerda la última URL del listado, solo en memoria). Si se llegó por un enlace directo, va al listado sin filtros. El formulario lleva "‹ Incidente" al editar y "‹ Incidentes" al crear. Eliminar también vuelve a ese listado.

| Acción | Cuándo se ve | Qué hace |
|---|---|---|
| Siguiente paso | `actions.change_status` y un estado propuesto | Botón principal "Pasar a En progreso" (`detail/DetailActions`, `nextStep`): de los estados permitidos que van más adelante, primero los que cierran el trabajo (Resuelto, Cerrado); nunca una salida. Luego el diálogo de nota ([ver abajo](#nota-al-cambiar-de-estado)) |
| Cambiar a… | `actions.change_status` | Los demás estados de `next_statuses`, en el menú ⋯ |
| Reabrir | `actions.reopen` | Toma el lugar del siguiente paso en lo cerrado o cancelado. Diálogo con el motivo, obligatorio |
| Solicitar reapertura | `actions.request_reopen` (y no `reopen`) | Igual, pero queda como solicitud para quien supervisa |
| Tomar | `actions.take` (sin `assign`) | En la ficha, junto a quien atiende. Se asigna el incidente a uno mismo |
| Asignar / Reasignar | `actions.assign` | En la ficha. Diálogo con la lista de personas asignables y "Sin asignar". "Guardar" se activa solo si la elección cambió |
| Editar | `actions.update` | En la ficha. Abre el formulario |
| Eliminar | `actions.delete` | En la ficha. Pide confirmación, elimina (borrado lógico) y vuelve al listado |

### Historial

Las 5 entradas más recientes (`detail/IncidentHistory`, `activityFeed` + `RECENT_ACTIVITY`), mensajes y cambios juntos, lo nuevo arriba. Cada una es un `detail/ActivityEntry`:

- **Mensaje:** un botón en toda la fila, con las iniciales (32 px), "Laura Gómez escribió · hace 2 horas" y el texto en dos líneas como máximo. Al tocarlo se abre el panel en Chat, sobre ese mensaje y resaltado.
- **Cambio:** una línea discreta, como "Laura Gómez cambió el estado · hace 2 horas", con el detalle ("Abierto → En progreso") y la nota de la persona en una sola línea (`HistoryNote`). Si la nota no cabe, "Ver nota completa" abre el panel en Actividad, sobre esa entrada y con la nota entera.
- **"Ver toda la actividad (N)"** abre el panel en Actividad. N suma los cambios y los mensajes.

### Panel de chat y actividad

Un `AppSheet` (`detail/IncidentActivityPanel`): desde la derecha en portátil y monitor, desde abajo de borde a borde en celular y tablet. Bajo el título, fijas, las pestañas "Chat (N) · Actividad (N)" (`AppSegmented` en el espacio `toolbar` de `AppSheet`).

- **Chat:** los mensajes en orden de conversación, lo viejo arriba y lo nuevo abajo (`ChatMessage`), con "Ver mensajes anteriores" arriba. Al abrirse baja al último mensaje, o al pedido si se llegó desde el historial. Los propios van a la derecha en burbuja azul con texto blanco; "Borrar" mientras la API lo permite, con confirmación, y queda "Mensaje eliminado". Lista con `role="log"`: los lectores de pantalla anuncian lo que llega.
- **Campo para escribir** (`ChatComposer`): al pie del panel, solo en la pestaña Chat y solo con `actions.comment`. Es el único lugar donde se escribe. Contador (2.000 caracteres); Enter envía y Shift+Enter hace un salto de línea (la ayuda está en el ícono de información). Sin permiso, el pie dice que se puede leer pero no escribir.
- **Actividad:** todo el historial y los mensajes, lo nuevo arriba, con las notas completas. Un mensaje lleva al chat en ese mensaje. "Ver actividad anterior" trae lo que falte del historial (todas sus páginas) y los mensajes anteriores.
- Con el detalle abierto, los mensajes cuentan como leídos en el centro de notificaciones (`watchConversation`).

### Nota al cambiar de estado

Elegir un estado abre un diálogo (`IncidentNoteDialog`, sobre `AppFormDialog`):

- Si el estado exige nota (`requires_note`), el campo aparece de una vez y es obligatorio: "¿Cómo se resolvió?" para Resuelto, "¿Por qué se cancela?" para Cancelado.
- Si no la exige, el diálogo solo confirma, con "Agregar una nota" para quien quiera dejarla.
- Pasar a un estado final avisa que después solo se podrá pedir que se reabra.
- Los errores quedan dentro del diálogo. Un conflicto de versión lo cierra, para que la página ofrezca "Recargar".

### Conversación

- **Dónde:** en la pestaña Chat del panel del detalle ([ver arriba](#panel-de-chat-y-actividad)).
- **Estado:** `useConversation` (mensajes ordenados por ULID, total, anteriores, escribir y borrar; `mergeComments` une sin repetir). Se carga al abrir el detalle, y un aviso de tiempo real o una reconexión la vuelven a pedir.
- **Desde un aviso:** `/incidentes/{id}?conversacion=1` quita el parámetro (el panel se cerraría si la ruta cambia con él abierto), abre el panel en Chat y, cuando termina de abrirse (`AppSheet` emite `shown`), pone el foco en el campo para escribir (`focusComposer`).

### Reapertura

- Si hay una solicitud pendiente, el detalle y la vista previa del listado (monitor) la muestran (`ReopenRequestNotice`): quién la pidió, cuándo y su motivo.
  - Quien puede decidir ve "Aprobar y reabrir" (pide confirmación) y "Rechazar" (diálogo con el motivo, obligatorio). Los dos lugares usan `useReopenDecision`, para preguntar y avisar igual.
  - El resto ve que está esperando respuesta.
- El listado marca "Reapertura pendiente" debajo del estado (tabla) o de las etiquetas (tarjetas), con `reopenRequested`, y filtra los que tienen una ("Con reapertura pendiente", `?reopen=1` en la URL).
- La decisión de qué se permite viene de `actions`; el front no calcula reglas ([ADR-0071](../decisiones/0071-nota-de-estado-y-reapertura.md)).
- **Colores:** salen de `incidentTones.ts`, porque los catálogos no guardan colores. Un estado o prioridad nuevos sin tono asignado se ven en neutro.

## Conflictos y errores

- **Conflicto de versión:**
  - Cada escritura envía la versión que se conocía. Si la API responde `VERSION_CONFLICT`, aparece un aviso con el botón "Recargar", tanto en el detalle como en el formulario.
  - No se reintenta solo: guardar encima de los cambios de otra persona los borraría sin que nadie lo note.
- **Otros errores** (estado final, ya asignado, transición inválida) se muestran en un aviso visible, con el mensaje del catálogo.
- **Cambios de otras personas en vivo** ([tiempo real](realtime.md)): el listado avisa "Hay N incidentes con cambios de otras personas · Actualizar" sin mover las filas; el detalle y la vista previa se actualizan solos; el formulario de edición avisa "Otra persona cambió este incidente" con "Recargar", sin tocar lo que se escribió. Así el conflicto de versión casi no llega a ocurrir.

## Fechas

`shared/format/dates.ts` concentra el formato:

- Fecha completa (`es-CO`, en la hora local).
- Fecha relativa ("hace un momento", "hace 20 minutos", "ayer"; pasada una semana, la fecha completa).
- Fecha corta para campos ("25 de oct, 10:30 a. m.").

## Capas

| Capa | Pieza | Qué hace |
|---|---|---|
| domain | `incident.ts` | Tipos del incidente, borrador, historial y catálogos; límites de largo |
| domain | `board.ts` | Vistas del tablero, columnas del Kanban, a dónde se puede mover una tarjeta y orden de la lista |
| domain | `calendar.ts`, `myDay.ts` | Grilla del mes y agenda por día de vencimiento; bloques de "Mi día" |
| domain | `listing.ts` | Criterios del listado (vista y grupo incluidos), valores por defecto, campos de orden y tamaños de página |
| domain | `grouping.ts`, `deadline.ts` | Criterios de grupo, nombres de los grupos y secciones de la página; tono y texto del avance del plazo |
| infrastructure | `boardApi.ts` | `GET /incidents/mine` |
| infrastructure | `listApi.ts` | Parámetros de `GET /incidents`, página de respuesta, restaurar y descarga de la exportación |
| infrastructure | `incidentsApi.ts`, `mappers.ts` | Llamadas a la API y traducción entre `snake_case` y el modelo |
| application | `useCatalogStore` | Catálogos, pedidos una vez por sesión |
| application | `useIncidentForm` | Carga, validación visual, vencimiento sugerido, idempotencia y envío |
| application | `useIncidentDetail` | Detalle, historial (primera página, total y el resto bajo demanda) y acciones, entre ellas la reapertura, con manejo de conflictos |
| application | `useMyIncidents`, `useBoardView`, `useCalendarMonth` | Tablero completo, mover una tarjeta con su conflicto de versión, y la vista elegida (URL y navegador) |
| application | `useAssignees` | Lista de personas asignables |
| application | `listQuery.ts` | Criterios ↔ URL, en las dos direcciones |
| application | `listReturn.ts` | Última URL del listado, para volver a él desde el detalle y el formulario |
| application | `useIncidentList` | Listado ligado a la URL (sobre `useRouteList`, que descarta respuestas viejas), restaurar, conteo para el panel de filtros y exportar (`exportCsv`, `exporting`) |
| application | `useListFilterOptions` | Opciones de cada filtro según los catálogos y los permisos |
| presentation | `IncidentListPage`, `IncidentFormPage`, `IncidentDetailPage`, `MyIncidentsPage` | Páginas |
| presentation | `board/`: `KanbanBoard`, `KanbanCard`, `MyIncidentsList`, `MyCalendar`, `CalendarMonthGrid`, `MyDay`, `IncidentRow` | Vistas de "Mis incidentes" |
| presentation | `list/`: `IncidentToolbar`, `IncidentListBar`, `IncidentGroupedList`, `IncidentLine`, `DeadlineMeter`, `IncidentFilterFields`, `IncidentMoreFilters`, `IncidentFiltersSheet`, `ActiveFilters`, `IncidentTable`, `IncidentCards`, `IncidentPreview`, `sortOptions.ts` | Piezas del listado. La tabla va sobre `AppDataTable` y el panel de filtros sobre `AppFilterSheet` |
| presentation | `useStatusChange`, `IncidentStatusButton` | Cambio de estado con el diálogo de nota, compartido por el detalle y la vista previa |
| presentation | `useNoteDialog`, `IncidentNoteDialog` | Diálogo con nota obligatoria u opcional: cambio de estado, pedir, hacer o rechazar una reapertura |
| presentation | `ReopenRequestNotice`, `useReopenDecision`, `HistoryNote` | Solicitud pendiente en el detalle y la vista previa, con su aprobación o rechazo; la nota de un cambio en una línea con su diálogo |
| presentation | `detail/`: `IncidentHeader`, `IncidentDescription`, `StatusPath`, `DeadlineBar`, `DetailActions`, `IncidentHistory`, `ActivityEntry`, `IncidentProfile`, `IncidentActivityPanel`, `ChatMessage`, `ChatComposer` | Piezas del detalle ([ADR-0083](../decisiones/0083-detalle-con-camino-y-actividad.md), [ADR-0084](../decisiones/0084-detalle-con-historial-y-panel-de-chat.md)) |
| presentation | `IncidentForm`, `IncidentBadges`, `IncidentFacts`, `AssignDialog` | Piezas reutilizables. Las insignias (`IncidentBadges`) van en las tarjetas del listado y la vista previa; en el detalle, estado y prioridad van dentro de "Datos" (`IncidentFacts` con `with-status`) |
| presentation | `incidentTones.ts`, `historyText.ts` | Colores por código y frases del historial |

## Dónde está en el código

- [`frontend/src/modules/incidents/`](../../frontend/src/modules/incidents/)
- [`frontend/src/shared/format/dates.ts`](../../frontend/src/shared/format/dates.ts)
- Enlace al listado filtrado desde otros módulos: [`listLink.ts`](../../frontend/src/modules/incidents/application/listLink.ts) (`incidentListLocation`)
- Pruebas: archivos `*.spec.ts` dentro del módulo y en `shared/format/`; flujos completos en [`frontend/e2e/incidents.spec.ts`](../../frontend/e2e/incidents.spec.ts) y [`frontend/e2e/export.spec.ts`](../../frontend/e2e/export.spec.ts).
- Exportar: [`IncidentToolbar.vue`](../../frontend/src/modules/incidents/presentation/list/IncidentToolbar.vue), [`IncidentListPage.vue`](../../frontend/src/modules/incidents/presentation/IncidentListPage.vue) y [`shared/download/saveFile.ts`](../../frontend/src/shared/download/saveFile.ts); decisión en el [ADR-0058](../decisiones/0058-exportacion-en-la-practica.md).
- Decisiones: [ADR-0012](../decisiones/0012-bloqueo-optimista.md), [ADR-0037](../decisiones/0037-idempotencia.md), [ADR-0043](../decisiones/0043-reglas-de-acceso-en-el-dominio.md), [ADR-0044](../decisiones/0044-listado-de-incidentes.md), [ADR-0045](../decisiones/0045-listado-en-el-front.md) y [ADR-0071](../decisiones/0071-nota-de-estado-y-reapertura.md)
