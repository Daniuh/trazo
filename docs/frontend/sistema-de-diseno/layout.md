---
title: Layout y navegación
description: Estructura de las pantallas y del menú en las cuatro vistas.
audience: tecnico
area: frontend
module: design
order: 15
---

# Layout y navegación

`AppShell` es la estructura común de todas las pantallas con sesión: sin encabezado, el contenido ocupa toda la pantalla y el menú va en un dock que flota abajo y centrado ([ADR-0079](../../decisiones/0079-menu-en-dock.md)).

| Vista | Dock | Campana y cuenta | Contenido |
|---|---|---|---|
| Celular | De borde a borde, con el nombre bajo cada ícono; "Más" si no caben todas las secciones | Al final del dock, tras una línea (36 px) | Ancho completo |
| Tablet | Isla centrada, solo íconos | Al final del dock, tras una línea | Ancho completo |
| Portátil | Isla centrada, solo íconos | Al final del dock | Ancho completo |
| Monitor | Isla centrada, solo íconos | Al final del dock | Centrado, con ancho máximo de 1280 px |

## El dock

`AppDock` muestra solo las secciones que la persona puede usar (`visibleNavigation`).

- **Tablet en adelante:** cada sección es un cuadro de 44 px con su ícono. El nombre sale encima al pasar el cursor o al enfocarlo con el teclado, y va como nombre accesible (`aria-label`). Esos nombres (`.q-tooltip`, en `theme/global.scss`) van con fondo oscuro, texto blanco y letra de 12 px: el gris de Quasar con 10 px no llega al contraste AA.
- **Celular:** el nombre va bajo el ícono, porque en una pantalla táctil no hay cursor que lo muestre. La campana y la cuenta van al final, más angostas (36 px, `$control-height-compact`). Cada sección tiene un poco de aire a los lados y el nombre va pegado al ícono (`$dock-label-gap`). Para que ningún nombre se corte, los lugares para secciones dependen del ancho (`dockSlots`): 4 desde 400 px, 3 entre 340 y 399 px y 2 en los más angostos (`layout.smallPhoneWidth`, `layout.tinyPhoneWidth`). Si hay más secciones, se muestran las primeras y **"Más"**, que abre el resto en un panel inferior (`splitDock`). Así, en un celular de 390 px el Super admin ve Inicio, Incidentes y "Más" (Auditoría, Usuarios, Roles y Sistema).
- **Sección actual:** fondo azul oscuro de marca (`$color-primary`) con ícono y texto en blanco (5:1), igual que el botón principal, y `aria-current="page"`. Se decide por la dirección (`inSection`): "Incidentes" sigue marcada en el detalle, el formulario y "Mis incidentes". "Inicio" solo en su propia página (`exact` en `NavItem`). "Más" se marca si la página actual es una de las que guarda.
- **Medidas:** alto `$dock-height` (56 px) o `$dock-height-mobile` (72 px, para que el fondo de la sección activa tenga aire arriba y abajo), separación del borde `$dock-offset` (12 px; en celular 8 px), esquinas `$radius-dock` y sombra `$shadow-overlay`. Respeta la zona segura de los celulares con barra de gestos (`env(safe-area-inset-bottom)`). Queda bajo los menús, paneles, diálogos y avisos (`$z-dock`).
- **Espacio para el dock:** el contenido deja libre abajo `$dock-clearance` (`$dock-clearance-mobile` en celular), así el dock nunca tapa lo último de la página. Las pantallas que llenan el alto (el Kanban y Mi día de [Mis incidentes](../incidents.md)) lo restan a su alto.

**"Saltar al contenido"** es lo primero que recibe el foco al usar Tab: está oculto hasta entonces y, al activarlo, lleva el foco al contenido (`main#contenido`) sin recorrer el menú (WCAG 2.4.1). No cambia la URL: el router usa el historial, no el `#`. El dock va después del contenido en el orden del teclado.

**Aviso de mantenimiento:** con el modo activo, quien sigue trabajando ve arriba del contenido "Modo mantenimiento activo", con un enlace a Sistema ([Módulo de sistema](../system.md#aviso-de-mantenimiento)).

Sin encabezado, el logo solo aparece en la pantalla de carga y en las pantallas sin sesión; para volver al inicio está "Inicio" en el dock.

La vista se vuelve a crear cuando cambia la ruta o sus parámetros (por ejemplo, de editar un incidente a crear uno nuevo), pero no cuando cambian solo los filtros de la URL.

## Avisos

Las notificaciones emergentes (`notify`, `notifyError`) salen arriba a la derecha: abajo están el dock y los paneles inferiores.

## Campana de notificaciones

La campana del [centro de notificaciones](../notifications.md) (`NotificationBell`) va al final del dock en las cuatro vistas, con su nombre al pasar el cursor desde tablet.

## La cuenta de la persona

`AppUserMenu` es un círculo con las iniciales de quien tiene la sesión abierta (32 px, `$avatar-size`, azul oscuro de marca con letras blancas, 5:1). Va junto a la campana. Al tocarlo abre un menú (`AppMenu`, hacia arriba desde el dock) con:

- El nombre y el rol.
- **Mi cuenta** (ícono de persona).
- **Cerrar sesión** (ícono de salida), que pide confirmación ("¿Cerrar sesión?") por si se tocó sin querer.

Su nombre accesible es "Tu cuenta: " seguido del nombre, como texto oculto dentro del botón y no en `aria-label`, para que no choque con las iniciales visibles. La campana hace lo mismo con su número ("9+"). Las iniciales salen del primer y del último nombre (`shared/format/initials.ts`).

## Navegación por módulos

Cada módulo aporta sus entradas al menú en `modules/<módulo>/navigation.ts` (etiqueta, ícono, ruta, orden y permiso). El dock las reúne solo y muestra únicamente las entradas cuyo permiso tiene la persona (`visibleNavigation`).

Las rutas de cada módulo (`modules/<módulo>/routes.ts`) se registran dentro del layout; las que deben verse sin sesión, como el inicio de sesión, se exportan aparte como `publicRoutes`.

## Dónde está en el código

- [`frontend/src/app/layout/`](../../../frontend/src/app/layout/): `AppShell.vue`, `AppDock.vue` (+ `dock.ts`) y `AppUserMenu.vue`
- Campana: [`NotificationBell.vue`](../../../frontend/src/modules/notifications/presentation/NotificationBell.vue). Iniciales: [`initials.ts`](../../../frontend/src/shared/format/initials.ts)
- Avisos: [`useFeedback.ts`](../../../frontend/src/shared/ui/feedback/useFeedback.ts)
- [`frontend/src/app/navigation.ts`](../../../frontend/src/app/navigation.ts) y [`frontend/src/app/router/index.ts`](../../../frontend/src/app/router/index.ts)
- [`frontend/src/design/tokens/layout.ts`](../../../frontend/src/design/tokens/layout.ts) y [`spacing.scss`](../../../frontend/src/design/tokens/spacing.scss)
