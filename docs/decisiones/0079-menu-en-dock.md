---
title: "ADR-0079: Menú en un dock abajo y centrado, sin encabezado"
description: "El menú lateral y el encabezado se reemplazan por un dock que flota abajo y centrado en las cuatro vistas; los avisos pasan arriba a la derecha"
audience: tecnico
area: decisiones
tags: [frontend]
order: 89
---

# ADR-0079: Menú en un dock abajo y centrado, sin encabezado

- **Estado:** Aceptada. Reemplaza la navegación por vista de la fase 1.7 (menú lateral expandido o plegado, compacto en tablet y hamburguesa en celular) y el perfil al pie del menú lateral del [ADR-0077](0077-centro-de-notificaciones.md).
- **Fecha:** 2026-11-01

## Contexto
El menú lateral ocupaba 240 px a la izquierda en portátil y monitor. Es justo el espacio que necesitan las pantallas más anchas: el Kanban y Mi día, las tablas y el dashboard. En el celular, la hamburguesa obligaba a abrir un menú para cambiar de sección. Además, se buscaba que Trazo no se viera como el panel de administración de siempre. Con el menú abajo, el encabezado quedaba solo con el logo, que no aporta nada que "Inicio" no dé.

## Alternativas evaluadas
- **Dock abajo y centrado**, como la barra de tareas de Windows 11 o el Dock del Mac, en las cuatro vistas.
- **Mantener el menú lateral** con la opción de plegarlo.
- **Menú arriba**, en el encabezado, con las secciones en fila.

Para los nombres de cada sección en el dock:

- **Solo íconos**, con el nombre al pasar el cursor o al enfocar con el teclado (tablet en adelante).
- **Ícono y nombre siempre visibles.**

Para el encabezado:

- **Quitarlo:** el contenido empieza arriba y lo que había (la campana y la cuenta en el celular) va al dock.
- **Mantenerlo** con el logo (y en el celular, la campana y la cuenta).

Para la cuenta de la persona:

- **Un círculo con sus iniciales** que abre un menú con nombre, rol, Mi cuenta y Cerrar sesión.
- **Dos botones sueltos** (Mi cuenta y Cerrar sesión) en el dock.

## Decisión
- **Dock** (`AppDock`) que flota abajo y centrado, con borde, esquinas redondeadas y sombra de elemento flotante. Muestra solo las secciones permitidas, igual que antes (`visibleNavigation`).
  - **Tablet, portátil y monitor:** una isla del ancho de sus íconos. El nombre de cada sección sale encima al pasar el cursor o al enfocarlo, y va como nombre accesible. Tras una línea, al final, la campana y la cuenta, como la bandeja de Windows.
  - **Celular:** de borde a borde, con el nombre bajo cada ícono, porque en una pantalla táctil no hay cursor que lo muestre. La campana y la cuenta van al final, como en las demás vistas, un poco más angostas (36 px). Los lugares para las secciones dependen del ancho, para que ningún nombre se corte ni quede pegado al borde: 4 desde 400 px, 3 entre 340 y 399 px y 2 en los más angostos. Si hay más secciones, las primeras y **"Más"**, que abre el resto en un panel inferior.
- **Sin encabezado:** el contenido empieza arriba de la pantalla. El logo deja de estar fijo (sigue en la pantalla de carga y en las pantallas sin sesión) e "Inicio" en el dock lleva a la página de inicio.
- **Sección actual por la dirección:** se marca con el fondo azul oscuro de marca y `aria-current="page"`. "Incidentes" sigue marcada en el detalle, el formulario y "Mis incidentes"; antes solo se marcaba en su ruta exacta. "Más" se marca si la página actual es una de las que guarda.
- **Cuenta:** el círculo con las iniciales abre un menú (`AppMenu`, que se abre donde cabe, aquí hacia arriba) con nombre, rol, **Mi cuenta** y **Cerrar sesión**. Cerrar sesión sigue pidiendo confirmación.
- **Espacio para el dock:** cada página deja libre abajo el alto del dock y su separación (`$dock-clearance`), así el dock nunca tapa lo último. El Kanban y Mi día restan ese espacio a su alto y siguen sin barra en la página.
- **Avisos arriba a la derecha:** abajo están el dock y los paneles inferiores.

## Alternativas descartadas
- **Menú lateral:** quita ancho en todas las pantallas para algo que se usa de vez en cuando, y se ve igual que el resto de este tipo de aplicaciones.
- **Menú arriba:** en el celular no cabe y termina otra vez en hamburguesa; además, arriba queda lejos del pulgar.
- **Nombres siempre visibles en tablet en adelante:** el dock se vuelve el doble de ancho y pierde la forma que se buscaba. Con cursor o teclado, el nombre aparece igual.
- **Mantener el encabezado:** una franja fija de 56 px solo con el logo es ruido: quita alto en todas las pantallas y repite lo que hace "Inicio". En el celular habría sido el lugar de la campana y la cuenta, que caben en el dock.
- **Botones sueltos para la cuenta:** en el dock ocupan dos lugares de sección y no dicen de quién es la sesión. El [ADR-0077](0077-centro-de-notificaciones.md) los había preferido porque al pie del menú lateral había espacio para el nombre y el rol; en el dock no lo hay, y el menú muestra el nombre y el rol antes de elegir.

## Consecuencias
- El contenido ocupa todo el ancho y todo el alto (en monitor sigue centrado con ancho máximo). La barra de Personalizar del dashboard queda fija arriba de la pantalla.
- Desaparecen el encabezado (y sus tokens `$header-height` y `$border-width`), el botón para plegar el menú, su preferencia guardada en el navegador (`useMenuPreference`) y la hamburguesa.
- Cerrar sesión pasa a ser un toque más: abrir la cuenta y elegir.
- Las pruebas E2E llegan a la cuenta por el botón "Tu cuenta" en las cuatro vistas.

## Dónde está en el código
- [`AppDock.vue`](../../frontend/src/app/layout/AppDock.vue) y su lógica en [`dock.ts`](../../frontend/src/app/layout/dock.ts) (`dockSlots`, `splitDock`, `inSection`), con pruebas en [`dock.spec.ts`](../../frontend/src/app/layout/dock.spec.ts).
- [`AppShell.vue`](../../frontend/src/app/layout/AppShell.vue), [`AppUserMenu.vue`](../../frontend/src/app/layout/AppUserMenu.vue) y [`NotificationBell.vue`](../../frontend/src/modules/notifications/presentation/NotificationBell.vue).
- Tokens `$dock-*` y `$radius-dock` en [`spacing.scss`](../../frontend/src/design/tokens/spacing.scss), `$z-dock` en [`accessibility.scss`](../../frontend/src/design/tokens/accessibility.scss) y `smallPhoneWidth` en [`layout.ts`](../../frontend/src/design/tokens/layout.ts).
- Avisos: [`useFeedback.ts`](../../frontend/src/shared/ui/feedback/useFeedback.ts) y `.q-notifications__list--top` en [`global.scss`](../../frontend/src/design/theme/global.scss).
