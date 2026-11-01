---
title: "ADR-0048: Diseño del dashboard guardado widget por widget, con los ocultos en su lugar"
description: "Diseño del dashboard guardado widget por widget, con los ocultos en su lugar"
audience: tecnico
area: decisiones
order: 58
---

# ADR-0048: Diseño del dashboard guardado widget por widget, con los ocultos en su lugar

- **Estado:** Aceptada
- **Fecha:** 2026-10-31

## Contexto
[ADR-0030](0030-dashboard-personalizable.md) decidió guardar el diseño en tablas relacionales (`dashboard_widgets` y `user_dashboard_widgets`). Faltaba definir cuatro cosas:

- Cómo se distingue "nunca lo personalizó" de "quitó todos los widgets".
- Qué pasa con un widget oculto.
- Cómo se arma el diseño original para roles con distintos widgets.
- Qué pasa cuando el catálogo cambia.

## Alternativas evaluadas
Qué se guarda:
1. Solo los widgets visibles.
2. **Una fila por widget permitido, con su lugar y si está visible.**

Diseño original:
1. Posiciones fijas por widget en el catálogo.
2. Un diseño original distinto por rol.
3. **Acomodar los widgets permitidos en el orden del catálogo, fila por fila, y hacer crecer el último de cada fila para no dejar huecos.**

## Decisión
- **Una fila por widget permitido.** Sin filas, la persona ve el diseño original. Con filas, ve lo que guardó, aunque haya ocultado todo.
- **El oculto conserva su lugar**, para volver a donde estaba al agregarlo de nuevo.
- **Diseño original acomodado** (alternativa 3) a partir de `sort_order` y el tamaño original de cada widget.
- **Widgets nuevos del catálogo:** aparecen ocultos para quien ya personalizó. Si cambian los topes de un widget, el tamaño guardado se ajusta solo al leerlo.
- **Guardar reemplaza el diseño completo** en una transacción, con la fila del usuario bloqueada para que dos guardados a la vez no se mezclen.

## Alternativas descartadas
- **Solo los visibles:** "quitó todos" y "nunca personalizó" serían iguales, y un widget que se vuelve a agregar perdería su lugar.
- **Posiciones fijas en el catálogo:** a quien le falta un widget (el solicitante no tiene "Sin asignar") le queda un hueco en la grilla.
- **Un diseño por rol:** son datos que mantener cada vez que se agrega un rol o un widget, y una persona puede tener varios roles.

## Consecuencias
- Cada persona tiene a lo sumo una fila por widget del catálogo (hoy, 10).
- El dominio (`DashboardGrid`) concentra la validación: topes, grilla de 12 columnas y choques. La base suma una restricción CHECK para que nadie deje un widget fuera de la grilla.
