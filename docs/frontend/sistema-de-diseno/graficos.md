---
title: Gráficos
description: Gráficos propios en SVG y HTML (barras, dona y columnas), con los colores de los tokens y accesibles sin depender del color.
audience: tecnico
area: frontend
module: sistema-de-diseno
order: 7
---

# Gráficos

Los gráficos son componentes propios, sin librerías (ver [ADR-0029](../../decisiones/0029-graficos-propios.md)). Viven en `src/shared/ui/charts/`, toman los colores del mapa de tonos y se ajustan al ancho de su contenedor.

| Componente | Para qué | Datos |
|---|---|---|
| `AppBarList` | Barras horizontales: nombre, barra proporcional al mayor y valor. Cada fila puede ser un enlace | `ChartItem[]` |
| `AppDonutChart` | Dona con el total en el centro y una leyenda con cantidad y detalle (p. ej. el porcentaje). Cada fila de la leyenda puede ser un enlace | `ChartItem[]` |
| `AppColumnChart` | Columnas agrupadas por punto (p. ej. creados y resueltos por día), con leyenda y fechas de inicio, mitad y fin | `ChartPoint[]` y `ChartSeries[]` |

`ChartItem` lleva `label`, `value`, `tone`, un `detail` opcional ("2 vencidos", "35 %") y un destino opcional (`to`).

## Reglas

- **Nada se comunica solo con color.** Cada valor también va en texto: en las barras al lado, en la dona en la leyenda y en las columnas en una tabla para lectores de pantalla.
- **Accesibilidad:**
  - Las barras y la leyenda de la dona son listas reales, así que un lector de pantalla lee "Abierto, 141".
  - El dibujo de la dona es decorativo para los lectores de pantalla, porque la leyenda ya lo dice todo.
  - Las columnas tienen una tabla oculta con cada punto, y al pasar el cursor cada grupo muestra sus valores.
- **Colores:** el color base de cada tono (`$tones`). El fondo de las barras y de la dona es el borde suave (`$color-border`), nunca blanco puro.
- **Números** con separador de miles de Colombia (`formatNumber`, "1.234").
- **Medidas** como tokens: `$chart-bar-height`, `$chart-label-width`, `$chart-donut-size` y `$chart-columns-height`.
- **Porciones vacías:** la dona no las dibuja, pero las deja en la leyenda con su cero.

## Dónde está en el código

- [`frontend/src/shared/ui/charts/`](../../../frontend/src/shared/ui/charts/)
- [`frontend/src/shared/format/numbers.ts`](../../../frontend/src/shared/format/numbers.ts)
- Pruebas: [`charts.spec.ts`](../../../frontend/src/shared/ui/charts/charts.spec.ts)
