---
title: Tokens de diseño
description: Variables de diseño de Trazo y cómo se conectan con Quasar.
audience: tecnico
area: frontend
module: design
order: 10
---

# Tokens de diseño

Todo valor visual vive en `src/design/tokens/`. Los componentes solo usan tokens y mixins (`@use '@/design/tokens' as *`); Stylelint rechaza colores, funciones de color y `@media` escritos fuera de `design/`.

| Archivo | Contenido |
|---|---|
| `colors.scss` | Paleta, neutros, roles y el mapa de tonos de etiqueta |
| `typography.scss` | Familia, pesos y tamaños |
| `spacing.scss` | Escala de espaciado, radios y medidas del layout |
| `shadows.scss` | Sombra de elementos flotantes, foco y duraciones |
| `breakpoints.scss` | Las cuatro vistas y sus mixins |
| `layout.ts` | Medidas que se necesitan como números en JS: ancho desde el que el dock del celular deja 5 lugares (`smallPhoneWidth`), separación y margen de los menús desplegables (`menuGap`, `menuViewportMargin`, los usa `AppMenu`), alto máximo de los menús con calendario y alto de fila y separación de la grilla editable del dashboard (`grid-layout-plus`), iguales a `$dashboard-row-height` y `$space-4` |

## Colores

| Token | Valor | Uso |
|---|---|---|
| `$color-blue-base` · `$color-green-base` · `$color-orange-base` | `#03A8D5` · `#86BB24` · `#FF9829` | Acentos, gráficos, íconos y puntos de prioridad (con texto negro: 6,5 a 8,4:1) |
| `$color-blue-strong` · `$color-green-strong` · `$color-orange-strong` | `#027695` · `#507016` · `#995B19` | Botones, texto de color y fondos de etiqueta (≥ 5:1 con blanco) |
| `$color-red` | `#C93A3A` | Errores, prioridad crítica y "Vencido" (5,1:1 con blanco) |
| `$color-page` | `#EEF3F6` | Fondo de página (azulado suave) |
| `$color-surface` | `#F7FAFB` | Tarjetas, tablas, campos y encabezado |
| `$color-border` · `$color-border-strong` | `#DDE5EA` · `#C9CED4` | Bordes |
| `$color-text-secondary` | `#5F6670` | Texto secundario (5,2 a 5,8:1) |
| `$color-text` | `#161616` | Texto principal |

No se usa blanco puro en ninguna superficie: cansa la vista en jornadas largas.

### Tonos de etiqueta

El mapa `$tones` define, para cada tono, el fondo y el color de texto ya resuelto con su contraste. Así es imposible pintar una etiqueta con fondo tintado y texto del mismo color.

| Tono | Fondo | Texto |
|---|---|---|
| `blue`, `green`, `orange` | Tono oscuro de la escala (`#027695` · `#507016` · `#995B19`) | Blanco |
| `neutral` | `#5F6670` | Blanco |
| `dark` | `#3D434A` | Blanco |
| `muted` | `#DDE5EA` | Gris secundario |
| `danger` | `#C93A3A` | Blanco |

Las etiquetas llevan esquinas casi rectas (`$radius-badge`, 4 px), para leerse como un dato de la fila y no como un botón. Los puntos de prioridad (`AppDot`) y los gráficos usan el color base (`$chart-fills`). El mapa de riesgo del dashboard usa `$risk-levels` (cuatro intensidades con fondo y texto ya resueltos: negro sobre los rojos claros, blanco sobre el rojo armonizado) y celdas de `$risk-cell-height`; el flujo de estados pasa a fila desde `$flow-row-from` (ancho del propio widget) con etapas de al menos `$flow-stage-min-width`. La barra del avance del plazo de la lista de incidentes también (`$deadline-fills`: verde con holgura, naranja desde el 75 %, rojo al vencer), sobre `$color-border`; su texto lleva colores con contraste (`$color-success`, `$color-danger` o el secundario). Medidas: `$deadline-meter-width` (72 px) y `$deadline-meter-height` (6 px); iniciales en las filas, `$avatar-size-sm` (24 px).

## Tipografía

Inter Variable, autoalojada (sin servicios de terceros), con tres pesos: 400, 500 y 600.

| Token | Tamaño | Uso |
|---|---|---|
| `$font-size-xs` | 12 px | Texto auxiliar |
| `$font-size-sm` | 13 px | Etiquetas y botones |
| `$font-size-md` | 14 px | Texto base |
| `$font-size-lg` | 16 px | Subtítulos |
| `$font-size-xl` | 20 px | Títulos de sección |
| `$font-size-2xl` | 28 px | Título de página |

## Espaciado y radios

Escala de 4 px (`$space-1` a `$space-7`: 4, 8, 12, 16, 24, 32 y 48). Radios de 8 px en controles, 10 px en tarjetas, 4 px en etiquetas de estado y filtros activos (`$radius-badge`) y en los paneles desplegables (`$radius-menu`) y redondo completo en chips y barras. El área táctil mínima es de 44 px (`$touch-target`); con mouse (portátil y monitor), botones y controles miden 36 px (`$control-height-compact`). Los campos miden 40 px (`$field-height`); los botones que van en la misma fila de una barra de filtros toman ese alto para quedar parejos.

### Dashboard y gráficos

| Token | Valor | Uso |
|---|---|---|
| `$dashboard-row-height` | 4,5 rem | Alto de una fila de la grilla de 12 columnas (portátil y monitor) |
| `$dashboard-resize-handle` | 1,25 rem | Manija para cambiar el tamaño en el modo Personalizar |
| `$dashboard-edit-border` | azul base | Marco punteado de los widgets y de la zona de destino al arrastrar |
| `$dashboard-drop-zone` | azul base al 15 % | Fondo de la zona donde cae el widget que se arrastra |
| `$shadow-dragging` | sombra amplia | Widget que se arrastra o se estira: flota sobre los demás |
| `$menu-wide-width` | 20 rem | Menús con una descripción por opción ("Agregar widget") |
| `$qr-size` | 11 rem | Código QR del MFA (se escanea bien desde cualquier teléfono) |
| `$code-column-min-width` | 8,5 rem | Columna de cada código de recuperación |
| `$letter-spacing-code` | 0,12 em | Separación de caracteres en códigos y claves, para leerlos sin error |
| `$chart-bar-height` | 0,625 rem | Grosor de las barras horizontales |
| `$chart-label-width` | 7 rem | Columna de nombres en las barras |
| `$chart-donut-size` | 8 rem | Diámetro de la dona |
| `$chart-columns-height` | 8 rem | Alto de las columnas de la tendencia |

## Accesibilidad

`accessibility.scss` reúne las utilidades compartidas. `@include visually-hidden` oculta un texto a la vista y lo deja para los lectores de pantalla (etiquetas de campos que ya se entienden por su contenido, tablas de datos de los gráficos). Es la única definición: ningún componente repite esas reglas.

## Conexión con Quasar

`src/design/theme/quasar-variables.scss` asigna los tokens a las variables de Quasar (`$primary`, `$negative`, tipografía, radios y breakpoints). No define valores propios: si cambia un token, Quasar cambia con él. `global.scss` aplica la fuente, el fondo, el foco visible y la preferencia de reducir movimiento.

## Dónde está en el código

- [`frontend/src/design/`](../../../frontend/src/design/)
- [`frontend/stylelint.config.js`](../../../frontend/stylelint.config.js)
- Decisiones: [ADR-0027](../../decisiones/0027-diseno-por-tokens.md) y [ADR-0028](../../decisiones/0028-identidad-visual.md)
