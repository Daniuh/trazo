---
title: Cifras del dashboard con caché
description: Cómo se calculan las cifras de cada widget con la visibilidad de la persona, cuándo salen de la caché y cómo se mantienen al día.
audience: tecnico
area: flujos
module: dashboard
order: 1
---

# Cifras del dashboard con caché

El dashboard es la página de inicio. Cada persona ve sus cifras con la misma regla de visibilidad del listado: quien tiene `dashboard.view.any` ve el "Resumen general"; quien tiene `dashboard.view.own`, "Mi resumen". Las cifras se guardan en caché por un minuto y se descartan de golpe en cuanto cambia cualquier incidente.

```flujo
carriles: [Persona, Navegador, API, Redis, Base de datos]
pasos:
  - id: abre
    carril: Persona
    tipo: inicio
    texto: Abre el inicio
    va: diseno
  - id: diseno
    carril: Navegador
    texto: Pide GET /dashboard
    detalle: Devuelve el título y los widgets de su diseño (el guardado o el original), solo los que su rol puede usar.
    va: cifras
  - id: cifras
    carril: Navegador
    texto: Pide GET /dashboard/metrics con sus widgets
    detalle: '`?widgets=` con los códigos visibles y `trend_days=7|30|90` para las tendencias.'
    va: permitido
  - id: permitido
    carril: API
    tipo: decision
    texto: ¿Puede ver el dashboard y cada widget pedido?
    detalle: Cada widget puede exigir un permiso propio (`dashboard_widgets.permission_id`).
    si: generacion
    no: e422
  - id: e422
    carril: API
    tipo: error
    texto: Responde 403 o 422
  - id: generacion
    carril: Redis
    texto: Lee la generación actual
    detalle: 'La clave de cada cifra lleva un número de generación y el alcance de la persona: `dashboard:{generación}:{cifra}:{alcance}`. Quienes ven todo (`all`) comparten la misma caché; los demás tienen la suya, porque sus cifras dependen de quiénes son.'
    va: hay
  - id: hay
    carril: Redis
    tipo: decision
    texto: ¿La cifra está en caché?
    si: usa
    no: calcula
  - id: calcula
    carril: Base de datos
    texto: Calcula con la visibilidad de la persona
    detalle: 'Consultas agregadas sobre índices hechos para el dashboard (`dashboard_counts`, `dashboard_assignee`…), con los días en la zona del negocio. Medidas por debajo de 60 ms con 100.000 incidentes.'
    va: guarda
  - id: guarda
    carril: Redis
    texto: Guarda la cifra por 60 s
    detalle: '`DASHBOARD_CACHE_SECONDS`. Solo arreglos y valores simples, nunca objetos de PHP.'
    va: usa
  - id: usa
    carril: API
    texto: Responde las cifras con su filtro
    detalle: 'Cada cifra trae el `filter` del listado que la explica y la hora de la más antigua, para mostrar "actualizado hace…".'
    va: muestra
  - id: muestra
    carril: Navegador
    tipo: fin
    texto: Dibuja los widgets; un clic abre el listado filtrado
    detalle: Gráficos propios en SVG (barras, dona, columnas, área y anillo), con una tabla oculta para lectores de pantalla.
  - id: cambia
    carril: API
    tipo: inicio
    texto: Un incidente cambia en cualquier lugar
    detalle: 'El repositorio emite `IncidentChanged` después del commit.'
    va: sube
  - id: sube
    carril: Redis
    texto: Sube la generación en 1
    detalle: Incremento atómico. Las claves viejas no se buscan ni se borran; nadie las vuelve a pedir y expiran solas.
    va: aviso
  - id: aviso
    carril: Navegador
    tipo: fin
    texto: El inicio abierto vuelve a pedir las cifras
    detalle: Por tiempo real, como máximo una vez cada 10 s y nunca mientras se personaliza.
```

Son dos caminos: abrir el inicio (pasos 1 a 10) y lo que pasa cuando un incidente cambia (pasos 11 a 13), con su propio inicio.

## Qué ve la persona en cada error

| Código | HTTP | Cuándo | Mensaje en pantalla |
|---|---|---|---|
| `WIDGET_NOT_AVAILABLE` | 422 | Se pidió un widget que su cuenta ya no puede usar (por ejemplo, le quitaron un permiso) | Uno de los widgets ya no está disponible para tu cuenta. Recarga la página para ver tu dashboard actual. |
| `FORBIDDEN_ACTION` | 403 | Su rol no tiene dashboard | No tienes permiso para hacer esta acción. Si crees que deberías tenerlo, habla con un administrador. |

Si no carga el diseño, la página muestra el error con "Reintentar". Si lo que falla son las cifras, el diseño se queda en pantalla y el error aparece encima de la grilla, también con "Reintentar".

## Dónde está en el código

- [`ReadDashboardMetrics`](../../../backend/app/Modules/Dashboard/Application/ReadDashboardMetrics.php), [`WidgetMetrics`](../../../backend/app/Modules/Dashboard/Application/Metrics/WidgetMetrics.php) y [`MetricSources`](../../../backend/app/Modules/Dashboard/Application/Metrics/MetricSources.php).
- [`CacheMetricsCache`](../../../backend/app/Modules/Dashboard/Infrastructure/CacheMetricsCache.php) e [`InvalidateDashboardMetrics`](../../../backend/app/Modules/Dashboard/Infrastructure/InvalidateDashboardMetrics.php): caché por generación.
- [`EloquentDashboardQueries`](../../../backend/app/Modules/Dashboard/Infrastructure/EloquentDashboardQueries.php): consultas y su visibilidad.
- [`useDashboard`](../../../frontend/src/modules/dashboard/application/useDashboard.ts) y [`DashboardPage`](../../../frontend/src/modules/dashboard/presentation/DashboardPage.vue).
- Explicación del módulo: [Módulo Dashboard](../../backend/dashboard.md).
