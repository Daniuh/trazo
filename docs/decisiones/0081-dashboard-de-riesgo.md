---
title: "ADR-0081: Dashboard centrado en el riesgo"
description: "El diseño original del dashboard pasa a pulso con cumplimiento del plazo, mapa de riesgo, flujo de estados y balance del backlog; los widgets anteriores siguen disponibles"
audience: tecnico
area: decisiones
order: 91
---

# ADR-0081: Dashboard centrado en el riesgo

- **Estado:** Aceptada. Amplía el catálogo del [ADR-0047](0047-metricas-del-dashboard.md) y cambia el diseño original; el modo Personalizar ([ADR-0050](0050-editor-del-dashboard.md)) no cambia.
- **Fecha:** 2026-10-31

## Contexto
El dashboard abría con cuatro tarjetas iguales (total, pendientes, vencidos y sin asignar), barras por estado, una dona de prioridades y columnas de creados contra resueltos. Es la estructura de casi cualquier panel, y mostraba cuántos incidentes hay, no dónde está el riesgo. Desde el [ADR-0080](0080-lista-agrupada-con-plazo.md) el back sabe en qué grupo de vencimiento está cada incidente y si los terminados se resolvieron a tiempo.

## Alternativas evaluadas
- **Mantener los widgets y cambiar solo el estilo.**
- **Reemplazar el diseño original por cuatro piezas nuevas**, dejando las anteriores en el catálogo:
  - **Pulso de la operación:** una frase con lo esencial, el cumplimiento del plazo (resueltos a tiempo en 30 días) y tres cifras.
  - **Mapa de riesgo:** prioridad contra vencimiento (vencido, hoy, 7 días, después), solo pendientes.
  - **Flujo de estados:** las etapas conectadas, con las salidas aparte.
  - **Balance del backlog:** los pendientes de cada día en el periodo.
- **Quitar los widgets anteriores** del catálogo.

Para el balance del backlog:

- **Reconstruirlo** desde los pendientes de hoy hacia atrás, con lo que entró y salió cada día.
- **Guardar una foto diaria** de los conteos en una tabla.

## Decisión
- **Cuatro widgets nuevos** (`operation_pulse`, `risk_map`, `status_flow`, `backlog_balance`), con la misma visibilidad, caché y permisos que el resto. Forman el diseño original junto a "Próximos a vencer", "Asignados a mí" y "Carga por agente".
- **Los anteriores siguen en "Agregar widget"**, fuera del diseño original. La columna nueva `dashboard_widgets.in_default_layout` dice qué widgets arman el original; los demás aparecen ocultos, como cualquier widget que una persona no tiene en su diseño.
- **Lo que pide la prueba sigue a la vista:** el total va en el pulso; por estado, en el flujo; vencidos, en el pulso y en el mapa; y por prioridad, en la columna "Total" del mapa (todos los de cada prioridad, en cualquier estado), separada de las de vencimiento.
- **Pulso:** total, pendientes, vencidos (y qué parte de los pendientes son), críticos pendientes, creados y resueltos hoy (día del negocio) y el cumplimiento: de los resueltos en los últimos 30 días, cuántos antes de su vencimiento. La frase la arma el front con esos números.
- **Mapa de riesgo:** cada celda con su número y su filtro del listado. Los grupos son los mismos de la lista (`DueWindow`). El color crece con la cantidad; el texto es negro o blanco según el contraste.
- **Flujo de estados:** sale del mismo conteo por estado. El camino son los estados en su orden; las salidas son los estados finales a los que se llega directo desde el inicial (Cancelado), así cambiar el catálogo no exige tocar código.
- **Balance:** se reconstruye. Pendientes de un día = pendientes del día siguiente − creados ese día siguiente + los que salieron ese día siguiente. "Salir" es resolverse (`resolved_at`) o terminar sin resolverse (cancelado: su última actualización).

## Alternativas descartadas
- **Solo cambiar el estilo:** la estructura seguía siendo la de cualquier panel.
- **Quitar los anteriores:** quien ya los usa en su diseño guardado los perdería, y siguen siendo útiles.
- **Foto diaria:** exige una tabla y una tarea programada más, y no tendría historia hasta que pasen los días. La reconstrucción da el periodo completo desde ya.

## Consecuencias
- Quien ya guardó su diseño lo conserva; los widgets nuevos le aparecen en "Agregar widget". "Restaurar original" lo lleva al diseño nuevo.
- El balance es una aproximación: un incidente reabierto cuenta según su estado actual, y una cancelación se fecha con la última actualización del incidente.
- Medido con 100.000 incidentes en [Rendimiento](../base-de-datos/rendimiento.md).

## Dónde está en el código
- Back: `WidgetCode`, las fuentes nuevas en [`MetricSources`](../../backend/app/Modules/Dashboard/Application/Metrics/MetricSources.php), su forma en [`RiskMetrics`](../../backend/app/Modules/Dashboard/Application/Metrics/RiskMetrics.php) y las consultas en [`EloquentDashboardQueries`](../../backend/app/Modules/Dashboard/Infrastructure/EloquentDashboardQueries.php).
- Front: [`PulseWidget.vue`](../../frontend/src/modules/dashboard/presentation/widgets/PulseWidget.vue), [`RiskMapWidget.vue`](../../frontend/src/modules/dashboard/presentation/widgets/RiskMapWidget.vue), [`StatusFlowWidget.vue`](../../frontend/src/modules/dashboard/presentation/widgets/StatusFlowWidget.vue) y [`BacklogWidget.vue`](../../frontend/src/modules/dashboard/presentation/widgets/BacklogWidget.vue).
