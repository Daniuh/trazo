---
title: Módulo de dashboard (frontend)
description: Página de inicio con los widgets de cada persona, en las cuatro vistas, y cómo cada cifra lleva al listado.
audience: tecnico
area: frontend
module: dashboard
order: 5
---

# Módulo de dashboard (frontend)

El dashboard es la página de inicio (`/`). Muestra los widgets que la persona tiene visibles, en el lugar que eligió. **El front no calcula nada:** las cifras, los porcentajes, qué es "vencido" y a qué filtro del listado lleva cada cifra llegan de la API (ver [Módulo Dashboard](../backend/dashboard.md)).

## Pantalla

| Vista | Forma |
|---|---|
| Portátil y monitor | Grilla de 12 columnas con el lugar y el tamaño que guardó la persona (`x`, `y`, `w`, `h`). Cada fila de la grilla mide `$dashboard-row-height` |
| Tablet | Dos columnas, en el orden de lectura de la persona (de arriba abajo y de izquierda a derecha). Los widgets anchos (8 columnas o más) ocupan la fila completa |
| Celular | Los indicadores de a dos por fila; el resto, a lo ancho |

- **Encabezado:** "Resumen general" (quien ve todo) o "Mi resumen" (quien ve lo suyo), "Actualizado hace…" con la explicación en el ícono de información, y el botón "Actualizar".
- **Cada cifra es un enlace** al listado con el filtro que la explica: "Vencidos" abre el listado con `overdue=1`, cada barra de estado abre ese estado, y así con todas.
- **Al volver a la pestaña**, si los datos tienen más de un minuto, se actualizan solos.
- **Estados:** esqueleto mientras carga; error con "Reintentar" (del diseño o de las cifras); y un mensaje claro si la persona ocultó todos sus widgets.

## Widgets

| Widget | Cómo se ve |
|---|---|
| Total, Pendientes, Vencidos, Sin asignar | Cifra grande. "Vencidos" va en rojo cuando es mayor que cero y dice qué parte de los pendientes son |
| Por estado | Barras horizontales con el color de cada estado |
| Por prioridad | Dona con el total en el centro y una leyenda con cantidad y porcentaje |
| Creados vs. resueltos | Columnas por día, con 7, 30 o 90 días (`AppSegmented`) y los totales en texto |
| Próximos a vencer, Asignados a mí | Lista corta: código, título, prioridad, "Vencido" si aplica y cuándo vence; al final, "Ver los N en el listado" |
| Carga por agente | Barras con los pendientes de cada persona y cuántos ya vencieron |

Los colores salen de los tonos de estados y prioridades (`incidentTones.ts`). Nada se comunica solo con color: cada valor también va en texto.

## Personalizar

El modo "Personalizar" (mover, cambiar el tamaño, quitar, agregar y restaurar el original, solo en portátil y monitor) se construye en el siguiente bloque de esta fase. El repositorio ya expone `save` y `reset`, y el back ya valida el diseño.

## Capas

| Capa | Pieza | Qué hace |
|---|---|---|
| domain | `dashboard.ts` | Tipos del diseño y de los datos de cada widget; orden de lectura para celular y tablet |
| infrastructure | `dashboardApi.ts`, `mappers.ts` | Llamadas a la API y traducción de `snake_case` al modelo |
| application | `useDashboard` | Diseño y cifras de los widgets visibles en una sola consulta, período de la tendencia y actualización al volver a la pestaña |
| presentation | `DashboardPage`, `DashboardGrid` | Página y grilla en las cuatro vistas |
| presentation | `widgets/WidgetCard`, `WidgetContent`, `KpiWidget`, `DigestWidget`, `TrendWidget` | Marco de cada widget y su contenido |

Para llevar al listado filtrado, el módulo usa `incidentListLocation` del módulo de incidentes, que traduce el filtro a la URL igual que el propio listado.

## Dónde está en el código

- [`frontend/src/modules/dashboard/`](../../frontend/src/modules/dashboard/)
- Enlace al listado: [`incidentListLocation`](../../frontend/src/modules/incidents/application/listLink.ts)
- Gráficos: [Gráficos](sistema-de-diseno/graficos.md)
- Pruebas: [`dashboard.spec.ts`](../../frontend/src/modules/dashboard/dashboard.spec.ts)
- Decisiones: [ADR-0029](../decisiones/0029-graficos-propios.md), [ADR-0030](../decisiones/0030-dashboard-personalizable.md) y [ADR-0047](../decisiones/0047-metricas-del-dashboard.md)
