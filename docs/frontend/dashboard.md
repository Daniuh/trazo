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

- **Encabezado:** "Resumen general" (quien ve todo) o "Mi resumen" (quien ve lo suyo), "Actualizado hace…" con la explicación en el ícono de información, y los botones "Personalizar" (portátil y monitor) y "Actualizar".
- **Cada cifra es un enlace** al listado con el filtro que la explica: "Vencidos" abre el listado con `overdue=1`, cada barra de estado abre ese estado, y así con todas.
- **Al volver a la pestaña**, si los datos tienen más de un minuto, se actualizan solos.
- **En vivo:** cuando cambia un incidente, las cifras se refrescan solas, como máximo una vez cada 10 segundos y nunca en modo Personalizar ([tiempo real](realtime.md)).
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

Se edita en portátil y monitor con el botón "Personalizar". En tablet y celular no hay botón: un ícono de información junto al título explica que se personaliza desde un computador, y los widgets siguen el orden elegido allí.

- **Barra fija** arriba, con "Agregar widget", "Restaurar original", "Cancelar" y "Guardar" (activo solo si hay cambios).
- **Cada widget** muestra sus datos reales (sin enlaces activos), con un marco punteado, la agarradera y el menú ⋯ arriba a la derecha, y la manija para estirarlo en la esquina inferior derecha. Mientras se estira, aparece su medida en columnas × filas.
- **Topes:** la grilla no deja pasar del mínimo ni del máximo de cada widget, los mismos que valida el back.
- **Sin huecos:** después de cada cambio, los widgets suben hasta donde no choquen.
- **Teclado:** el menú ⋯ ofrece mover arriba, abajo, a la izquierda y a la derecha; más ancho, más angosto, más alto y más bajo; y quitar. Las opciones que chocan con un tope aparecen desactivadas.
- **Agregar widget** lista los ocultos con su nombre y qué muestran. El elegido entra al final, con el tamaño que tenía, y un aviso dice dónde quedó.
- **Restaurar original** pide confirmación y se aplica de inmediato.
- **Cambios sin guardar:** "Cancelar" pide confirmación para descartarlos, y salir de la página también avisa (al cambiar de sección y al cerrar o recargar la pestaña).
- **Ventana angosta:** si se achica a tamaño de tablet en pleno modo edición, el borrador se conserva y aparece el aviso para agrandarla.
- **Errores al guardar:** cada rechazo del back tiene su mensaje (superposición, tamaño fuera de rango, widget no disponible), visible en la página.

Mientras se personaliza, las cifras siguen al borrador: un widget recién agregado pide sus datos sin esperar a guardar.

## Capas

| Capa | Pieza | Qué hace |
|---|---|---|
| domain | `dashboard.ts` | Tipos del diseño y de los datos de cada widget; orden de lectura para celular y tablet |
| domain | `layoutEditing.ts` | Reglas puras para mover, estirar, quitar y agregar sin salirse de la grilla ni de los topes, con compactación vertical |
| infrastructure | `dashboardApi.ts`, `mappers.ts` | Llamadas a la API y traducción de `snake_case` al modelo |
| application | `useDashboard` | Diseño y cifras de los widgets visibles en una sola consulta, período de la tendencia y actualización al volver a la pestaña |
| application | `useDashboardEditor` | Borrador del modo Personalizar: si hay cambios, guardar, cancelar y restaurar el original |
| presentation | `DashboardPage`, `DashboardGrid` | Página y grilla en las cuatro vistas |
| presentation | `widgets/WidgetCard`, `WidgetContent`, `KpiWidget`, `DigestWidget`, `TrendWidget` | Marco de cada widget y su contenido |
| presentation | `edit/EditToolbar`, `AddWidgetMenu`, `DashboardEditor`, `EditableWidget` | Barra del modo Personalizar, menú de widgets ocultos, grilla editable (único uso de `grid-layout-plus`) y marco de cada widget en edición |

Para llevar al listado filtrado, el módulo usa `incidentListLocation` del módulo de incidentes, que traduce el filtro a la URL igual que el propio listado.

## Dónde está en el código

- [`frontend/src/modules/dashboard/`](../../frontend/src/modules/dashboard/)
- Enlace al listado: [`incidentListLocation`](../../frontend/src/modules/incidents/application/listLink.ts)
- Gráficos: [Gráficos](sistema-de-diseno/graficos.md)
- Personalizar: [`useDashboardEditor`](../../frontend/src/modules/dashboard/application/useDashboardEditor.ts), [`layoutEditing.ts`](../../frontend/src/modules/dashboard/domain/layoutEditing.ts) y [`presentation/edit/`](../../frontend/src/modules/dashboard/presentation/edit/)
- Aviso de cambios sin guardar: [`useLeaveGuard`](../../frontend/src/shared/ui/feedback/useLeaveGuard.ts)
- Pruebas: [`dashboard.spec.ts`](../../frontend/src/modules/dashboard/dashboard.spec.ts), [`layoutEditing.spec.ts`](../../frontend/src/modules/dashboard/layoutEditing.spec.ts) y E2E en [`e2e/dashboard.spec.ts`](../../frontend/e2e/dashboard.spec.ts)
- Decisiones: [ADR-0029](../decisiones/0029-graficos-propios.md), [ADR-0030](../decisiones/0030-dashboard-personalizable.md), [ADR-0047](../decisiones/0047-metricas-del-dashboard.md), [ADR-0048](../decisiones/0048-diseno-del-dashboard-por-persona.md) y [ADR-0050](../decisiones/0050-editor-del-dashboard.md)
