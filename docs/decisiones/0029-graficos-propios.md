---
title: "ADR-0029: Gráficos con componentes SVG propios"
description: "Gráficos con componentes SVG propios"
audience: tecnico
area: decisiones
order: 39
---

# ADR-0029: Gráficos con componentes SVG propios

- **Estado:** Aceptada
- **Fecha:** 2026-10-31

## Contexto
El dashboard necesita gráficos por estado y por prioridad. Deben respetar los tokens de diseño y adaptarse al tamaño de cada widget.

## Alternativas evaluadas
- Librerías de gráficos (ECharts, Chart.js, ApexCharts).
- Componentes propios en SVG.

## Decisión
Componentes propios en SVG (barras y dona). Toman los colores y la tipografía de los tokens y se ajustan al tamaño de su contenedor.

## Alternativas descartadas
- **Librerías:** suman peso al bundle y su sistema de estilos compite con los tokens. Con 4 a 6 categorías por gráfico, sus capacidades adicionales no se aprovechan.

## Consecuencias
- Hay control total del aspecto y la accesibilidad, sin dependencias externas.
- Si en el futuro hacen falta gráficos más complejos, podría justificarse una librería.
