---
title: Módulo de incidentes (frontend)
description: Formulario de crear y editar, detalle con acciones e historial, y cómo se manejan los conflictos.
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
| `/incidentes/nuevo` | Formulario de creación (menú "Nuevo incidente", visible solo con `incidents.create`) |
| `/incidentes/:id` | Detalle |
| `/incidentes/:id/editar` | El mismo formulario, para editar |

El listado (`/incidentes`) se suma con la fase del listado avanzado.

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
| Eliminar | `actions.delete` | Pide confirmación, elimina (borrado lógico) y vuelve al inicio |

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
| infrastructure | `incidentsApi.ts`, `mappers.ts` | Llamadas a la API y traducción entre `snake_case` y el modelo |
| application | `useCatalogStore` | Catálogos, pedidos una vez por sesión |
| application | `useIncidentForm` | Carga, validación visual, vencimiento sugerido, idempotencia y envío |
| application | `useIncidentDetail` | Detalle, historial y acciones con manejo de conflictos |
| application | `useAssignees` | Lista de personas asignables |
| presentation | `IncidentFormPage`, `IncidentDetailPage` | Páginas |
| presentation | `IncidentForm`, `IncidentActions`, `IncidentBadges`, `IncidentFacts`, `IncidentHistory`, `AssignDialog` | Piezas reutilizables (las insignias se reutilizarán en el listado) |
| presentation | `incidentTones.ts`, `historyText.ts` | Colores por código y frases del historial |

## Dónde está en el código

- [`frontend/src/modules/incidents/`](../../frontend/src/modules/incidents/)
- [`frontend/src/shared/format/dates.ts`](../../frontend/src/shared/format/dates.ts)
- Pruebas: archivos `*.spec.ts` dentro del módulo y en `shared/format/`.
- Decisiones: [ADR-0012](../decisiones/0012-bloqueo-optimista.md), [ADR-0037](../decisiones/0037-idempotencia.md) y [ADR-0043](../decisiones/0043-reglas-de-acceso-en-el-dominio.md)
