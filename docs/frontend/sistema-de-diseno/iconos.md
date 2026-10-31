---
title: Íconos
description: Íconos propios de Trazo, cómo se dibujan y cómo se agregan.
audience: tecnico
area: frontend
module: design
order: 13
---

# Íconos

Trazo usa íconos propios, dibujados en código con un estilo de contorno: grilla de 24 × 24, trazo de 1,5 px, puntas y esquinas redondeadas y sin relleno. Toman el color del texto que los rodea.

## Uso

```vue
<AppIcon name="incidents" />              <!-- decorativo, junto a un texto -->
<AppIcon name="trash" label="Eliminar" /> <!-- con significado propio, leído por lectores de pantalla -->
```

Tamaños: `sm` (16 px), `md` (20 px, por defecto) y `lg` (24 px).

## Cómo se agrega un ícono

Cada ícono es una lista de figuras SVG (`path`, `circle` o `rect`) en `src/design/icons/icons.ts`. Se guardan como datos y no como HTML para dibujarlos sin `v-html`, que está prohibido por seguridad.

1. Dibujarlo sobre la grilla de 24 con las mismas reglas de trazo.
2. Agregarlo a `icons.ts` con un nombre en inglés y en minúsculas.
3. Revisarlo en la guía visual junto al resto.

Los íconos internos de Quasar (por ejemplo, las flechas de un calendario) usan su juego oficial en SVG, empaquetado con la aplicación; nunca se carga una fuente de íconos externa.

## Dónde está en el código

- [`frontend/src/design/icons/icons.ts`](../../../frontend/src/design/icons/icons.ts)
- [`frontend/src/shared/ui/AppIcon.vue`](../../../frontend/src/shared/ui/AppIcon.vue)
