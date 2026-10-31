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
| Tablet | Compacto, solo íconos; el nombre aparece al pasar el cursor y cada enlace lo lleva como nombre accesible (`aria-label`) | Ancho completo |
| Portátil | Expandido, con íconos y nombres | Ancho completo |
| Monitor | Expandido | Centrado, con ancho máximo de 1280 px |

El logo del encabezado lleva al inicio. Su nombre accesible es el mismo texto que se ve ("Trazo"), para que quien navega por voz pueda nombrarlo tal como lo ve (WCAG 2.5.3).

La sección activa se marca con fondo neutro y una barra azul a la izquierda: nunca con fondo tintado y texto del mismo color. "Inicio" se marca solo en su ruta exacta (`exact` en `NavItem`), porque todas las demás rutas cuelgan de él.

La vista se vuelve a crear cuando cambia la ruta o sus parámetros (por ejemplo, de editar un incidente a crear uno nuevo), pero no cuando cambian solo los filtros de la URL.

## Menú de la persona conectada

A la derecha del encabezado, `AppUserMenu` muestra quién tiene la sesión abierta:

| Vista | Botón |
|---|---|
| Celular y tablet | Solo el ícono de usuario (44 px de alto) |
| Portátil y monitor | Ícono y nombre |

Al abrirlo se ven el nombre, el correo y los roles, y la opción "Cerrar sesión". Los menús desplegables usan la superficie suave con borde y sombra, nunca blanco puro.

## Navegación por módulos

Cada módulo aporta sus entradas al menú en `modules/<módulo>/navigation.ts` (etiqueta, ícono, ruta, orden y permiso). El menú las reúne solo y muestra únicamente las entradas cuyo permiso tiene la persona (`visibleNavigation`).

Las rutas de cada módulo (`modules/<módulo>/routes.ts`) se registran dentro del layout; las que deben verse sin sesión, como el inicio de sesión, se exportan aparte como `publicRoutes`.

## Dónde está en el código

- [`frontend/src/app/layout/`](../../../frontend/src/app/layout/), incluido `AppUserMenu.vue`
- [`frontend/src/app/navigation.ts`](../../../frontend/src/app/navigation.ts) y [`frontend/src/app/router/index.ts`](../../../frontend/src/app/router/index.ts)
- [`frontend/src/design/tokens/layout.ts`](../../../frontend/src/design/tokens/layout.ts)
