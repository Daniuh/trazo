---
title: Módulo Dashboard
description: Widgets, métricas con la visibilidad del listado, caché invalidada por cambios y diseño guardado por persona.
audience: tecnico
area: backend
module: dashboard
order: 5
---

# Módulo Dashboard

El dashboard resume los incidentes que cada persona puede ver: totales, pendientes, vencidos, distribución por estado y por prioridad, tendencia, listas cortas y carga por agente. Cada persona organiza sus widgets y el diseño se guarda en el servidor.

Tres reglas guían el módulo:

- **Las cifras usan la misma visibilidad que el listado** (`VisibilityScope`, traducida a SQL por `VisibilitySql`). Una cifra del dashboard nunca contradice lo que la persona encuentra al filtrar.
- **El back dice a qué filtro del listado lleva cada cifra** (`filter`). El front no decide qué es "pendiente" ni "vencido".
- **El diseño solo decide dónde va cada widget, nunca qué datos se ven.** Los permisos se validan en cada lectura y en cada guardado.

## Endpoints

| Método y ruta | Qué hace |
|---|---|
| `GET /api/v1/dashboard` | Diseño de la persona: alcance (`general` o `personal`), si lo personalizó y cada widget con sus topes, su lugar y si está visible |
| `GET /api/v1/dashboard/metrics?widgets=a,b&trend_days=30` | Datos de los widgets pedidos. `trend_days` admite 7, 30 o 90. `meta.generated_at` dice cuándo se calculó el dato más antiguo |
| `PUT /api/v1/dashboard/layout` | Guarda el diseño completo: `widgets: [{code, x, y, w, h, visible}]` |
| `DELETE /api/v1/dashboard/layout` | Vuelve al diseño original (borra lo guardado) |

Entrar exige `dashboard.view.own` o `dashboard.view.any`. Con `dashboard.view.any` el título es "Resumen general"; con el otro, "Mi resumen". Sin ninguno, la respuesta es 403 `FORBIDDEN_ACTION`.

## Widgets

| Código | Tipo | Permiso propio | Datos |
|---|---|---|---|
| `operation_pulse` | Gráfico | — | Total (todos los visibles), pendientes, vencidos y qué parte de los pendientes son, pendientes de la prioridad más alta, creados y resueltos hoy (día del negocio) y el cumplimiento del plazo: de lo resuelto en 30 días, qué porcentaje antes del vencimiento (`null` si no hay resueltos) |
| `risk_map` | Gráfico | — | Pendientes por prioridad (de la más alta a la más baja) y grupo de vencimiento (`overdue`, `today`, `week`, `later`, con los límites de `DueWindow`), cada celda con su filtro del listado; y por prioridad, el `total` en cualquier estado |
| `status_flow` | Gráfico | — | Cantidad por estado: el camino (`stages`) y las salidas (`exits`): los estados finales a los que se llega directo desde el inicial |
| `backlog_balance` | Gráfico | — | Pendientes de cada día del periodo (`trend_days`), reconstruidos desde los de hoy hacia atrás con lo que entró y salió cada día (resuelto, o terminado sin resolverse según su última actualización), y el cambio en el periodo |
| `total_incidents` | Número | — | Todos los visibles, en cualquier estado |
| `pending_incidents` | Número | — | Abiertos, en progreso o en espera |
| `overdue_incidents` | Número | — | Pendientes con el vencimiento pasado, y qué porcentaje de los pendientes son |
| `unassigned_incidents` | Número | `incidents.view.unassigned` | Pendientes sin asignar |
| `incidents_by_status` | Gráfico | — | Cantidad por estado, incluidos los que están en cero |
| `incidents_by_priority` | Gráfico | — | Cantidad y porcentaje por prioridad (los porcentajes siempre suman 100) |
| `created_vs_resolved` | Gráfico | — | Creados y resueltos por día, contados en la zona horaria del negocio |
| `due_soon` | Lista | — | Los 5 pendientes que vencen antes, en las próximas 48 horas, y el total |
| `assigned_to_me` | Lista | `incidents.view.assigned` | Los 5 pendientes propios que vencen antes, y el total |
| `workload_by_agent` | Gráfico | `incidents.assign` | Pendientes y vencidos de las 8 personas más cargadas |

El catálogo vive en la tabla `dashboard_widgets` (ver [diccionario](../base-de-datos/diccionario/dashboard.md)). Un widget solo se ofrece si además tiene implementación en el código (`WidgetCode`). Agregar uno nuevo es sumar el caso en `WidgetCode`, su consulta y su forma en `WidgetMetrics` (o `RiskMetrics`), y su fila en el seeder. `in_default_layout` dice si forma parte del diseño original ([ADR-0081](../decisiones/0081-dashboard-de-riesgo.md)).

## Grilla y diseño

- **Grilla de 12 columnas.** Cada widget tiene topes de ancho y alto en el catálogo. El servidor rechaza un diseño con widgets fuera de la grilla (`WIDGET_OUT_OF_GRID`), de tamaño inválido (`WIDGET_SIZE_OUT_OF_RANGE`, con los topes en `params`), repetidos (`WIDGET_DUPLICATED`), no disponibles para la persona (`WIDGET_NOT_AVAILABLE`) o visibles que chocan (`WIDGETS_OVERLAP`).
- **Diseño original** (`DashboardGrid::defaults`): los widgets permitidos marcados con `in_default_layout`, en el orden del catálogo, de izquierda a derecha y fila por fila. Si a una fila le sobra espacio, el último widget crece dentro de su tope. Los demás permitidos quedan ocultos, debajo de todo, listos para "Agregar widget".
- **Guardar** escribe una fila por widget permitido, con su lugar y si está visible. Un widget oculto conserva su lugar, para volver a donde estaba si se agrega de nuevo. Los que no se enviaron quedan ocultos, debajo de todo.
- **Widgets nuevos en el catálogo**: para quien ya personalizó, aparecen ocultos, listos para agregarse. Si el catálogo cambia los topes de un widget, el tamaño guardado se ajusta solo (`DashboardGrid::fit`).
- **Sin filas guardadas** la persona ve el diseño original. "Restaurar" borra sus filas.

## Métricas y caché

- **Fuentes compartidas** (`MetricSources`): total, pendientes, por estado y por prioridad salen de un solo conteo agrupado por estado y prioridad. Cada fuente se guarda en caché por separado.
- **Clave por alcance**: quienes ven todo comparten la caché (`all`). Para el resto, la clave lleva su id y sus permisos de visibilidad. "Asignados a mí" siempre lleva el id de quien pregunta.
- **Invalidación por generación**: cada cambio en un incidente (crear, editar, cambiar de estado, asignar, eliminar o restaurar) emite el evento `IncidentChanged` después de confirmar la transacción. El dashboard sube un número de generación que forma parte de todas las claves, así que la siguiente lectura calcula de nuevo. Lo anterior expira solo, sin buscar ni borrar claves.
- **Tiempo de vida de 60 segundos** (`DASHBOARD_CACHE_SECONDS`): cubre lo que cambia solo con la hora, como un pendiente que vence sin que nadie lo toque.
- **Tendencia en la hora del negocio** (`BUSINESS_TIMEZONE`, por defecto `America/Bogota`): MySQL agrupa por hora UTC y la aplicación arma los días locales. El día de hoy forma parte de la clave, así que la ventana avanza sola a medianoche. Para zonas con desfase de media hora, el reparto por hora es aproximado.
- **Sin N+1**: la cantidad de consultas no depende del volumen de datos (lo comprueba una prueba).

El rendimiento con 100.000 incidentes está medido en [Rendimiento](../base-de-datos/rendimiento.md).

## Capas

| Capa | Pieza | Qué hace |
|---|---|---|
| Domain | `WidgetCode`, `WidgetKind`, `WidgetDefinition` | Qué widgets existen, de qué tipo y con qué topes y permiso |
| Domain | `Placement`, `WidgetLayout`, `DashboardGrid` | Lugar en la grilla, diseño original y validación |
| Domain | `DashboardAccess`, `Percentages` | Quién entra y con qué alcance; porcentajes que suman 100 |
| Application | `ManageDashboardLayout` | Ver, guardar y restaurar el diseño |
| Application | `ReadDashboardMetrics`, `Metrics/MetricSources`, `Metrics/WidgetMetrics` | Datos de cada widget, en caché por alcance |
| Infrastructure | `EloquentDashboardQueries` | Agregados en SQL con la visibilidad del listado |
| Infrastructure | `DatabaseWidgetCatalog`, `DatabaseLayoutStore` | Catálogo y diseño guardado |
| Infrastructure | `CacheMetricsCache`, `InvalidateDashboardMetrics` | Caché con generación e invalidación al cambiar un incidente |
| Http | `DashboardController`, `DashboardResource`, `Requests/*` | Endpoints, forma JSON y validación de entrada |

## Dónde está en el código

- [`backend/app/Modules/Dashboard/`](../../backend/app/Modules/Dashboard/)
- Visibilidad compartida: [`VisibilitySql`](../../backend/app/Modules/Incidents/Infrastructure/VisibilitySql.php)
- Evento de cambios: [`IncidentChanged`](../../backend/app/Modules/Incidents/Application/Events/IncidentChanged.php)
- Configuración: [`backend/config/trazo.php`](../../backend/config/trazo.php) (`dashboard`)
- Pruebas: [`tests/Unit/Dashboard/`](../../backend/tests/Unit/Dashboard/) y [`tests/Feature/Dashboard/`](../../backend/tests/Feature/Dashboard/)
- Diseño centrado en el riesgo: [`RiskMetrics`](../../backend/app/Modules/Dashboard/Application/Metrics/RiskMetrics.php), las fuentes `onTime`, `risk` y `exits` en [`MetricSources`](../../backend/app/Modules/Dashboard/Application/Metrics/MetricSources.php) y la migración [`000043`](../../backend/database/migrations/2026_11_01_000043_add_in_default_layout_to_dashboard_widgets.php)
- Decisiones: [ADR-0030](../decisiones/0030-dashboard-personalizable.md), [ADR-0047](../decisiones/0047-metricas-del-dashboard.md), [ADR-0048](../decisiones/0048-diseno-del-dashboard-por-persona.md) y [ADR-0081](../decisiones/0081-dashboard-de-riesgo.md)
