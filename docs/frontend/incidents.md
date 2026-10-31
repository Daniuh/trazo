---
title: Módulo de incidentes (frontend)
description: Listado con filtros en la URL, formulario de crear y editar, detalle con acciones e historial, y cómo se manejan los conflictos.
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
| `/incidentes/:id` | Detalle |
| `/incidentes/:id/editar` | El mismo formulario, para editar |

## Listado

Cada persona ve solo lo que la API le deja ver (ver [Módulo Incidents](../backend/incidents.md)). El front no filtra por su cuenta.

| Vista | Forma |
|---|---|
| Monitor | Tabla. Elegir una fila abre a la derecha una **vista previa** con lo esencial, "Cambiar estado" y "Abrir detalle", sin salir del listado. Con la vista previa abierta se oculta la columna "Creado" |
| Portátil | Tabla. Elegir una fila abre el detalle |
| Tablet y celular | Tarjetas con código, título, estado, prioridad, asignado y vencimiento. Tocar una tarjeta abre el detalle |

**Barra de filtros**

- **Portátil y monitor:** búsqueda; estado, prioridad y asignado como selectores; y el menú "Más filtros" con los rangos de creación y de vencimiento, "Solo vencidos", "Creados por mí" y "Ver eliminados". El orden se cambia tocando el encabezado de la tabla.
- **Celular y tablet:** búsqueda, "Filtros (n)" y "Ordenar por". "Filtros" abre un panel inferior con los mismos filtros (estado, prioridad y asignado como chips). El panel se edita en borrador y el botón dice cuántos resultados daría antes de aplicarlo.
- **Filtros activos:** debajo de la barra, como etiquetas que se quitan con ✕, más "Limpiar filtros". Una etiqueta aparece cuando ya se conoce el nombre de su valor, para no mostrar códigos internos mientras cargan los catálogos.
- **Búsqueda:** se consulta al dejar de escribir. Con menos de 3 letras se avisa ("Escribe al menos 3 letras para buscar.") y no se consulta. Busca en el código (`INC-000123` o `123`), el título y la descripción.

**Qué opciones ve cada persona**

| Filtro | Quién lo ve |
|---|---|
| Asignado: todas las personas y "Sin asignar" | Quien puede asignar (`incidents.assign`) |
| Asignado: "Asignados a mí" y "Sin asignar" | Un agente, según lo que puede ver |
| Asignado | Un solicitante no lo ve: solo ve lo que creó |
| Ver eliminados | Quien puede restaurar (`incidents.restore`). En esa vista, cada fila ofrece "Restaurar" |

**Filtros en la URL.** Todo el criterio vive en la URL (`?status=open,in_progress&priority=critical&page=2`), así que un enlace compartido o una recarga muestran lo mismo. Lo que no se reconoce se ignora y vuelve al valor por defecto. Cambiar un filtro vuelve a la página 1.

**Paginación.** "1–20 de 534", páginas con saltos (…) y 20, 50 o 100 por página. En celular, solo "anterior", "Página 3 de 27" y "siguiente".

**Estados.** Esqueleto mientras carga la primera vez. Dos vacíos distintos: sin resultados para los filtros (con "Limpiar filtros") o sin incidentes todavía (con "Nuevo incidente", si se puede crear). Error con "Reintentar". Si llega una respuesta vieja después de una nueva, se descarta.

## Formulario (crear y editar)

| Vista | Forma |
|---|---|
| Portátil y monitor | Dos columnas: título y descripción a la izquierda; prioridad, vencimiento y asignación a la derecha. Botones al final, a la derecha |
| Celular y tablet | Una columna; botones apilados, con la acción principal arriba |

- **Validación visual:** campos obligatorios y largo máximo. La API valida todo de nuevo, y sus errores aparecen junto a cada campo.
- **Vencimiento:** `AppDateTimeField`, en la hora local, que viaja a la API en UTC.
  - Al crear, el campo vacío muestra "Sugerida: …" según la prioridad, y el calendario abre en esa fecha redondeada a la media hora. "Usar la sugerida" vuelve a dejarlo vacío para que lo calcule la API. La aclaración va en el ícono de información.
  - Al editar, el campo abre en el vencimiento guardado y solo se envía si cambió.
- **"Asignar a"** aparece solo al crear y solo para quien tiene `incidents.assign`. La lista viene de `/incidents/assignees`.
- **Idempotencia:** cada envío de creación lleva una `Idempotency-Key`. Si se reintenta el mismo envío, viaja la misma clave; si los datos cambiaron, una nueva. Mientras se guarda, el botón queda bloqueado.
- **Si el incidente ya no se puede editar** (porque otra persona lo cerró, por ejemplo), abrir la edición lleva al detalle.

## Detalle

| Vista | Forma |
|---|---|
| Portátil y monitor | Encabezado con código, título, estado, "Vencido" y prioridad, y los botones a la derecha. Debajo, la descripción y el historial a la izquierda, y la tarjeta "Datos" a la derecha |
| Celular y tablet | Una columna: encabezado, "Cambiar estado" a lo ancho más el menú ⋯, y luego datos, descripción e historial |

| Acción | Cuándo se ve | Qué hace |
|---|---|---|
| Cambiar estado | `actions.change_status` | Menú con `next_statuses`. Pasar a un estado final (Cerrado o Cancelado) pide confirmación |
| Tomar | `actions.take` | Se asigna el incidente a uno mismo |
| Asignar / Reasignar | `actions.assign` | Diálogo con la lista de personas asignables y "Sin asignar". "Guardar" se activa solo si la elección cambió |
| Editar | `actions.update` | Abre el formulario |
| Eliminar | `actions.delete` | Pide confirmación, elimina (borrado lógico) y vuelve al listado |

- **Historial:** frases claras, como "Laura Gómez cambió el estado: Abierto → En progreso", con la fecha relativa ("hace 2 horas", "ayer").
- **Colores:** salen de `incidentTones.ts`, porque los catálogos no guardan colores. Un estado o prioridad nuevos sin tono asignado se ven en neutro.

## Conflictos y errores

- **Conflicto de versión:**
  - Cada escritura envía la versión que se conocía. Si la API responde `VERSION_CONFLICT`, aparece un aviso con el botón "Recargar", tanto en el detalle como en el formulario.
  - No se reintenta solo: guardar encima de los cambios de otra persona los borraría sin que nadie lo note.
- **Otros errores** (estado final, ya asignado, transición inválida) se muestran en un aviso visible, con el mensaje del catálogo.

## Fechas

`shared/format/dates.ts` concentra el formato:

- Fecha completa (`es-CO`, en la hora local).
- Fecha relativa ("hace un momento", "hace 20 minutos", "ayer"; pasada una semana, la fecha completa).
- Fecha corta para campos ("25 de oct, 10:30 a. m.").

## Capas

| Capa | Pieza | Qué hace |
|---|---|---|
| domain | `incident.ts` | Tipos del incidente, borrador, historial y catálogos; límites de largo |
| domain | `listing.ts` | Criterios del listado, valores por defecto, campos de orden y tamaños de página |
| infrastructure | `listApi.ts` | Parámetros de `GET /incidents`, página de respuesta y restaurar |
| infrastructure | `incidentsApi.ts`, `mappers.ts` | Llamadas a la API y traducción entre `snake_case` y el modelo |
| application | `useCatalogStore` | Catálogos, pedidos una vez por sesión |
| application | `useIncidentForm` | Carga, validación visual, vencimiento sugerido, idempotencia y envío |
| application | `useIncidentDetail` | Detalle, historial y acciones con manejo de conflictos |
| application | `useAssignees` | Lista de personas asignables |
| application | `listQuery.ts` | Criterios ↔ URL, en las dos direcciones |
| application | `useIncidentList` | Estado del listado ligado a la URL, descarte de respuestas viejas y conteo para el panel de filtros |
| application | `useListFilterOptions` | Opciones de cada filtro según los catálogos y los permisos |
| presentation | `IncidentListPage`, `IncidentFormPage`, `IncidentDetailPage` | Páginas |
| presentation | `list/`: `IncidentToolbar`, `IncidentFilterFields`, `IncidentMoreFilters`, `IncidentFiltersSheet`, `ActiveFilters`, `IncidentTable`, `IncidentCards`, `IncidentPreview`, `sortOptions.ts` | Piezas del listado |
| presentation | `useStatusChange`, `IncidentStatusButton` | Cambio de estado con confirmación, compartido por el detalle y la vista previa |
| presentation | `IncidentForm`, `IncidentActions`, `IncidentBadges`, `IncidentFacts`, `IncidentHistory`, `AssignDialog` | Piezas reutilizables (las insignias se reutilizarán en el listado) |
| presentation | `incidentTones.ts`, `historyText.ts` | Colores por código y frases del historial |

## Dónde está en el código

- [`frontend/src/modules/incidents/`](../../frontend/src/modules/incidents/)
- [`frontend/src/shared/format/dates.ts`](../../frontend/src/shared/format/dates.ts)
- Pruebas: archivos `*.spec.ts` dentro del módulo y en `shared/format/`; flujos completos en [`frontend/e2e/incidents.spec.ts`](../../frontend/e2e/incidents.spec.ts).
- Decisiones: [ADR-0012](../decisiones/0012-bloqueo-optimista.md), [ADR-0037](../decisiones/0037-idempotencia.md), [ADR-0043](../decisiones/0043-reglas-de-acceso-en-el-dominio.md), [ADR-0044](../decisiones/0044-listado-de-incidentes.md) y [ADR-0045](../decisiones/0045-listado-en-el-front.md)
