---
title: Módulo de sistema (frontend)
description: Pantalla de sistema del Super admin: panel de salud con sus acciones, eventos agrupados y aviso de mantenimiento.
audience: tecnico
area: frontend
module: system
order: 9
---

# Módulo de sistema (frontend)

La pantalla "Sistema" del menú. Muestra lo que dice la API ([Módulo System](../backend/system.md)): el front no decide qué está bien o mal ni qué acción es sensible.

## Pantallas

| Ruta | Qué muestra | Permiso |
|---|---|---|
| `/sistema` | Lleva a la primera pestaña que la persona puede ver (en `beforeEnter`, como la auditoría) | — |
| `/sistema/salud` | Panel de salud | `system.health.view` |
| `/sistema/eventos` | Eventos del sistema | `system.logs.view` |

El menú muestra "Sistema" con cualquiera de los dos permisos; las pestañas se eligen con un control segmentado si hay más de una.

### Salud

- **Tarjetas:** una por componente (base de datos, Redis y caché, cola, workers, tareas programadas, tiempo real y almacenamiento), más "Estado general" y "Modo mantenimiento".
  - Cada una muestra su estado como etiqueta (Bien, Atención o Falla) y la medición en palabras: "Responde en 4,7 ms", "Última señal hace 20 s", "Libre: 150 GB de 475 GB (32 %)".
  - Qué es cada componente va en el ícono de información.
- **Grilla:** una columna en celular, dos en tablet, tres en portátil y monitor.
- **Se actualiza sola** cada 30 segundos, solo con la pestaña visible; al volver a ella se pide de una vez. "Actualizar" en "Estado general".
- **Acciones**, en la tarjeta a la que corresponden y solo con `system.actions.execute`:

  | Tarjeta | Acción |
  |---|---|
  | Redis y caché | Limpiar caché de datos |
  | Workers | Reiniciar workers |
  | Tiempo real | Reiniciar |
  | Modo mantenimiento | Activar o Desactivar |
  | Cola de trabajos | Ver jobs fallidos (cualquiera con la salud) |

  Cada acción abre el diálogo del código (`AppCodeDialog` con `useReauth`, pidiéndolo de una vez). El texto del diálogo explica qué va a pasar, así que también sirve de confirmación. Al terminar, un aviso y el panel se vuelve a pedir.
- **Jobs fallidos:** panel lateral en portátil y monitor, inferior en tablet y celular (`AppSheet`). Muestra el job, la cola, la fecha y la primera línea del error, nunca su contenido. Se reintentan uno a uno o todos, y se descartan uno a uno.

### Eventos

- **Filtros en la URL:** qué se ve (Pendientes, Resueltos o Todos; por defecto Pendientes, que son los abiertos y los revisados), severidad y tipo. `?view=all&severity=critical&type=job_failed`.
- **Lista:** de la última vez más reciente a la más antigua, con "Cargar más" (`useCursorList`, el mismo de la auditoría).
  - Monitor: tabla con el detalle al lado.
  - Portátil: tabla y el detalle en un panel a la derecha.
  - Tablet y celular: tarjetas y el detalle en un panel inferior.
- **Detalle:**
  - El mensaje, la severidad, las veces, la primera y la última vez.
  - El estado con quién lo cambió.
  - El contexto con nombres claros (Cuenta, Servicio, Ruta…).
  - El código de soporte para buscarlo en los logs.
- **Cambiar el estado:** "Marcar como revisado", "Marcar como resuelto" o "Reabrir", según el estado actual (`nextStatuses`). La fila se reemplaza sin volver a pedir la lista.

### Aviso de mantenimiento

Con el modo activo, la API responde 503 a todos menos a quien puede ejecutar acciones de sistema:

- **Quien queda fuera** ve "Trazo está en mantenimiento" (el mensaje de `SERVICE_UNAVAILABLE`). Al recargar llega a la pantalla "No pudimos cargar Trazo" con ese mensaje y "Reintentar".
- **Quien sigue trabajando** recibe la cabecera `X-Maintenance` en cada respuesta. El cliente HTTP la refleja en `maintenanceNotice` (`shared/http/maintenance.ts`), y el shell muestra un aviso fijo con un enlace a Sistema. El aviso desaparece solo con la primera respuesta sin la cabecera.

## Capas

| Capa | Pieza | Qué hace |
|---|---|---|
| domain | `system.ts` | Tipos de salud, eventos y jobs; vistas y estados que piden a la API; estados siguientes |
| infrastructure | `systemApi.ts` | Llamadas a `/system/*` y traducción de la respuesta; el código de reautenticación va en `mfa_code` |
| application | `useHealth`, `useFailedJobs` | Panel con actualización cada 30 s; lista de jobs fallidos |
| application | `useSystemEvents`, `systemQuery.ts` | Lista por cursor ligada a la URL, catálogo de tipos y cambio de estado; criterios ↔ URL y nombres de las rutas |
| presentation | `SystemPage` | Encabezado y pestañas |
| presentation | `health/HealthView`, `HealthCard`, `FailedJobsSheet` | Panel, tarjeta de un componente y jobs fallidos |
| presentation | `events/EventsView`, `EventToolbar`, `EventTable`, `EventCards`, `EventDetail` | Lista, filtros y detalle |
| presentation | `systemTexts.ts` | Nombres, explicaciones, mediciones en palabras, etiquetas y tonos |

`useReauth` pasó de `modules/admin` a `shared/ui/feedback` y `needsCode` a `shared/http`: ahora los usan la administración y el sistema.

## Dónde está en el código

- [`frontend/src/modules/system/`](../../frontend/src/modules/system/)
- [`shared/http/maintenance.ts`](../../frontend/src/shared/http/maintenance.ts), [`shared/ui/feedback/useReauth.ts`](../../frontend/src/shared/ui/feedback/useReauth.ts) y [`app/layout/AppShell.vue`](../../frontend/src/app/layout/AppShell.vue)
- Pruebas: [`system.spec.ts`](../../frontend/src/modules/system/system.spec.ts) y [`e2e/system.spec.ts`](../../frontend/e2e/system.spec.ts)
- Decisión: [ADR-0059](../decisiones/0059-sistema-en-la-practica.md)
