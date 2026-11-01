---
title: Marca
description: Logo de Trazo, favicon y pantallas de carga.
audience: todos
area: frontend
module: design
order: 14
---

# Marca

## Logo

"Traz" en Inter y la "o" convertida en un aro de tres trazos (azul, verde y naranja) con puntas redondeadas y pequeños espacios entre ellos: el ciclo de vida de un incidente. Alude a la "o" de Psicol sin copiarla.

| Pieza | Uso |
|---|---|
| `AppLogo` | Logo completo; su tamaño sigue el tamaño de letra del contenedor |
| `AppLogoMark` | Solo el aro (encabezados compactos, espacios pequeños) |
| `public/favicon.svg` | Ícono de la pestaña del navegador |

## Pantallas de carga

| Cuándo | Qué se ve | Cómo |
|---|---|---|
| Al abrir la aplicación | "Trazo" con el aro girando, centrado | CSS puro en `index.html`: aparece antes de que cargue JavaScript y Vue lo reemplaza al montar la app |
| Al cambiar de sección | El mismo logo, en el área de contenido | `useRouteLoading` en el layout |
| Dentro de una pantalla | Esqueletos con la forma del contenido | `AppSkeleton` |

En los dos primeros casos el logo solo aparece si la carga dura más de ~300 ms, para que no parpadee en cargas rápidas. Si la persona activó "reducir movimiento" en su sistema, el aro se muestra quieto.

## Dónde está en el código

- [`frontend/src/shared/ui/brand/`](../../../frontend/src/shared/ui/brand/)
- [`frontend/index.html`](../../../frontend/index.html) y [`frontend/src/design/brand/boot-loader.scss`](../../../frontend/src/design/brand/boot-loader.scss)
- [`frontend/src/app/layout/useRouteLoading.ts`](../../../frontend/src/app/layout/useRouteLoading.ts)
- Decisión: [ADR-0028](../../decisiones/0028-identidad-visual.md)
