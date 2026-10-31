---
title: Layout y navegación
description: Estructura de las pantallas y del menú en las cuatro vistas.
audience: tecnico
area: frontend
module: design
order: 15
---

# Layout y navegación

`AppShell` es la estructura común de todas las pantallas con sesión: encabezado, menú lateral y contenido.

| Vista | Menú | Contenido |
|---|---|---|
| Celular | Oculto; se abre con el botón de menú y se cierra al elegir una sección | Ancho completo |
| Tablet | Compacto, solo íconos; el nombre aparece al pasar el cursor | Ancho completo |
| Portátil | Expandido, con íconos y nombres | Ancho completo |
| Monitor | Expandido | Centrado, con ancho máximo de 1280 px |

La sección activa se marca con fondo neutro y una barra azul a la izquierda: nunca con fondo tintado y texto del mismo color.

## Navegación por módulos

Cada módulo aporta sus entradas al menú en `modules/<módulo>/navigation.ts` (etiqueta, ícono, ruta, orden y permiso). El menú las reúne solo; cuando exista la autenticación, mostrará únicamente las entradas que el usuario tiene permitido ver.

Las rutas de cada módulo (`modules/<módulo>/routes.ts`) se registran dentro del layout; las que deben verse sin sesión, como el inicio de sesión, se exportan aparte como `publicRoutes`.

## Dónde está en el código

- [`frontend/src/app/layout/`](../../../frontend/src/app/layout/)
- [`frontend/src/app/navigation.ts`](../../../frontend/src/app/navigation.ts) y [`frontend/src/app/router/index.ts`](../../../frontend/src/app/router/index.ts)
- [`frontend/src/design/tokens/layout.ts`](../../../frontend/src/design/tokens/layout.ts)
