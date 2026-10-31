---
title: Componentes base
description: Componentes reutilizables de Trazo, cuándo usar cada uno y sus reglas.
audience: tecnico
area: frontend
module: design
order: 12
---

# Componentes base

Viven en `src/shared/ui/` y son los únicos que tocan Quasar directamente. Las pantallas los usan sin conocer la librería. En desarrollo, la **guía visual** (`/guia-visual`) los muestra todos con sus estados, para revisarlos en las cuatro vistas.

| Componente | Para qué |
|---|---|
| `AppButton` | Acciones. Variantes `primary`, `secondary`, `danger` y `link`; con `loading` queda bloqueado y lo anuncia (`aria-busy`). Alto táctil de 44 px en celular y tablet. |
| `AppIcon` | Íconos propios (ver [Íconos](iconos.md)). |
| `AppInfoTip` | Ícono de información con una ayuda oculta. Ver la regla abajo. |
| `AppBadge` | Etiqueta sólida para estados. Recibe un tono; el contraste ya viene resuelto. |
| `AppDot` | Punto de color con texto, para prioridades. Distinto a propósito de la etiqueta del estado. |
| `AppCard` | Superficie con título y acciones opcionales. |
| `AppTextField` · `AppSelect` | Campos con etiqueta arriba, ayuda en el ícono de información y error siempre visible debajo. Comparten la envoltura `AppField`. Con `type="password"`, el campo trae un botón para mostrar u ocultar la contraseña; `textarea` empieza con espacio para varias líneas; `datetime-local` usa el selector de fecha y hora del navegador. `AppSelect` muestra su texto de ejemplo (en gris) cuando no hay valor. |
| `AppAlert` | Aviso siempre visible dentro de la página (error de un formulario o información imprescindible). Tonos `danger` (rol `alert`) e `info` (rol `status`); fondo de superficie con borde de color, nunca fondo tintado. |
| `AppSkeleton` · `AppEmptyState` · `AppErrorState` | Estados de carga, vacío y error. El de error usa el catálogo de mensajes y ofrece "Reintentar". |
| `useFeedback()` | `confirm()` abre el diálogo de confirmación (devuelve `true` o `false`); `notify()` y `notifyError()` muestran notificaciones. Los errores no se cierran solos. |
| `useViewport()` | Vista actual: `mobile`, `tablet`, `laptop` o `desktop`. |

## Ícono de información

Toda ayuda o aclaración que no sea imprescindible va en `AppInfoTip`, para mantener las pantallas limpias:

- Se muestra al pasar el cursor, al enfocarlo con el teclado o al tocarlo en el celular; se cierra al salir, con Escape o al tocar fuera.
- La burbuja calcula su posición para no salirse nunca de la pantalla.
- Es accesible: el botón tiene nombre propio y la ayuda está asociada con `aria-describedby`.

**Nunca** se usa para errores, mensajes de validación ni instrucciones necesarias para completar una acción: eso siempre queda visible.

## Reglas comunes

- Estilos en un solo archivo por componente, *mobile first*, con bloques `tablet`, `laptop` y `desktop`.
- Clases en BEM (`bloque__elemento--modificador`), verificado por Stylelint.
- Fondo de color siempre con texto negro o blanco según contraste.
- Comentarios: encabezado breve en cada archivo y comentarios cortos solo donde aportan.

## Dónde está en el código

- [`frontend/src/shared/ui/`](../../../frontend/src/shared/ui/)
- Guía visual: [`frontend/src/app/pages/DesignGuidePage.vue`](../../../frontend/src/app/pages/DesignGuidePage.vue)
- Pruebas: [`frontend/src/shared/ui/components.spec.ts`](../../../frontend/src/shared/ui/components.spec.ts)
