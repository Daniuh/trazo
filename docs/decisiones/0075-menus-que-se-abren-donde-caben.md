---
title: "ADR-0075: Menús que se abren donde caben"
description: "Cada menú mide su tamaño y el espacio libre antes de abrirse: abajo, arriba, al lado o bajando la página"
audience: tecnico
area: decisiones
order: 85
---

# ADR-0075: Menús que se abren donde caben

- **Estado:** Aceptada
- **Fecha:** 2026-10-31

## Contexto
Todos los menús se abrían hacia abajo. Cuando no cabían, Quasar hacía una de dos cosas: los volteaba hacia el lado con más espacio y los recortaba a ese espacio, con una barra de desplazamiento interna (el calendario de "Vence" en una ventana baja), o los dejaba salirse por debajo de la pantalla ("Cambiar estado" al final de la vista previa del listado). En los dos casos había que desplazarse para ver las opciones o los botones del pie ("Listo", "Cancelar").

## Alternativas evaluadas
- **Medir antes de abrir y elegir en orden: abajo, arriba, al lado y, si nada alcanza, bajar la página lo justo**, en un componente común.
- **Dejar el volteo de Quasar** y solo subir el alto máximo de los menús.
- **Abrir siempre como panel** (diálogo centrado o desde abajo) los menús grandes, también en portátil y monitor.
- **Corregir cada menú por separado** con posiciones fijas según la pantalla.

## Decisión
- `menuPlacement.ts` tiene la regla, sin dependencias de la interfaz y con pruebas:
  1. **Abajo**, como siempre, si cabe entero.
  2. **Arriba**, alineado al mismo borde del botón, si abajo no cabe y arriba sí.
  3. **Al lado** (izquierda o derecha, donde haya más espacio), arrancando a la altura del botón y subiendo lo justo para quedar dentro de la ventana, si el menú cabe a lo alto.
  4. **Abajo después de bajar la página** lo necesario, sin que el botón salga por arriba, si nada de lo anterior alcanza.
- `AppMenu` envuelve `QMenu` y aplica la regla: al abrirse mide el alto natural del menú (aunque Quasar ya lo haya recortado) y el espacio alrededor del botón, elige la posición, baja la página si hace falta y le pide a Quasar que reubique. Mientras mide, el menú no se ve, así no salta de un lugar a otro.
- Todos los menús lo usan: `AppActionMenu` ("Cambiar estado", ⋯), `AppPopupField` (calendarios), el menú de la cuenta, el ⋯ del listado y "Agregar widget". Ningún componente usa `QMenu` directamente.
- La separación con el botón (6 px) y el margen con los bordes de la ventana (8 px) son tokens (`layout.menuGap`, `layout.menuViewportMargin`).
- En celular y tablet, los calendarios siguen abriéndose como panel inferior.
- `AppSelect` (Estado, Prioridad, Asignado) sigue con el menú propio de `QSelect`, que ya se voltea hacia el lado con más espacio. Sus listas son cortas y casi nunca lo necesitan; si una lista larga lo pidiera, se le aplica la misma regla.

## Alternativas descartadas
- **Solo el volteo de Quasar:** elige el lado más amplio pero recorta el menú a ese espacio, que es justo lo que generaba la barra interna.
- **Siempre como panel:** en portátil y monitor rompe la relación visual con el campo y obliga a mover la mirada al centro de la pantalla por una decisión de una línea.
- **Una posición fija por menú:** depende del alto de cada ventana y se rompe al cambiar el contenido (un estado con más transiciones, un mes de seis semanas).

## Consecuencias
- Los menús se abren completos, con sus opciones y botones a la vista, sin barras internas.
- Un menú nuevo se construye sobre `AppMenu` y hereda la regla sin código propio de posición.
- Si la pestaña está en segundo plano, el navegador pausa los cuadros de dibujo; `AppMenu` tiene un tope de tiempo para no quedarse esperando.

## Dónde está en el código
- Regla: [`menuPlacement.ts`](../../frontend/src/shared/ui/menuPlacement.ts) y sus pruebas [`menuPlacement.spec.ts`](../../frontend/src/shared/ui/menuPlacement.spec.ts).
- Componente: [`AppMenu.vue`](../../frontend/src/shared/ui/AppMenu.vue). Tokens: [`layout.ts`](../../frontend/src/design/tokens/layout.ts).
- Usos: [`AppActionMenu.vue`](../../frontend/src/shared/ui/AppActionMenu.vue), [`AppPopupField.vue`](../../frontend/src/shared/ui/form/AppPopupField.vue), [`AppUserMenu.vue`](../../frontend/src/app/layout/AppUserMenu.vue), [`IncidentToolbar.vue`](../../frontend/src/modules/incidents/presentation/list/IncidentToolbar.vue) y [`AddWidgetMenu.vue`](../../frontend/src/modules/dashboard/presentation/edit/AddWidgetMenu.vue).
