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
| `AppAreaChart` | Línea con área debajo para una serie en el tiempo (p. ej. los pendientes de cada día), en escala del mínimo al máximo del periodo para que la tendencia se note, con fechas de inicio, mitad y fin | `AreaPoint[]` (`key`, `label`, `value`) |
| `AppRing` | Anillo de avance con el porcentaje en el centro (p. ej. el cumplimiento del plazo). Sin dato, el anillo queda vacío y dice "—" | `percent` (o `null`) y `label` |

`ChartItem` lleva `label`, `value`, `tone`, un `detail` opcional ("2 vencidos", "35 %") y un destino opcional (`to`).

## Reglas

- **Nada se comunica solo con color.** Cada valor también va en texto: en las barras al lado, en la dona en la leyenda y en las columnas en una tabla para lectores de pantalla.
- **Accesibilidad:**
  - Las barras y la leyenda de la dona son listas reales, así que un lector de pantalla lee "Abierto, 141".
  - El dibujo de la dona es decorativo para los lectores de pantalla, porque la leyenda ya lo dice todo.
  - Las columnas y el área tienen una tabla oculta con cada punto, y al pasar el cursor cada punto muestra su valor.
  - El anillo es una imagen con nombre ("Cumplimiento del plazo: 96 %").
- **Columnas en escala de raíz cuadrada.** Un día atípico (por ejemplo, 190 incidentes frente a 10 de costumbre) se ve más alto, pero no deja a los demás como una línea en el piso. El orden y las diferencias se conservan, y el valor exacto de cada día está al pasar el cursor y en la tabla. Arriba a la derecha, "Día con más" dice el valor del día más alto.
- **El alto de las columnas lo pone la tarjeta**, nunca la forma del dibujo: el SVG va sobre un lienzo que ocupa el espacio disponible (con un mínimo). Con pocos puntos (7 días) las columnas se quedan del ancho que tendrían con 14 y centradas en su día, para que no se vean como bloques.
- **Colores:** el color base de cada tono (`$tones`). El fondo de las barras y de la dona es el borde suave (`$color-border`), nunca blanco puro.
- **Números** con separador de miles de Colombia (`formatNumber`, "1.234").
- **Medidas** como tokens: `$chart-bar-height`, `$chart-label-width`, `$chart-donut-size`, `$chart-columns-height` (también el alto mínimo del área) y `$chart-ring-size`. El área se pinta con `$chart-area-fill`.
- **Porciones vacías:** la dona no las dibuja, pero las deja en la leyenda con su cero.

## Dónde está en el código

- [`frontend/src/shared/ui/charts/`](../../../frontend/src/shared/ui/charts/)
- [`frontend/src/shared/format/numbers.ts`](../../../frontend/src/shared/format/numbers.ts)
- Pruebas: [`charts.spec.ts`](../../../frontend/src/shared/ui/charts/charts.spec.ts)
