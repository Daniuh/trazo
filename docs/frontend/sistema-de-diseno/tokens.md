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
| `layout.ts` | Medidas que Quasar necesita como números en JS (ancho del menú) |

## Colores

| Token | Valor | Uso |
|---|---|---|
| `$color-blue-base` · `$color-green-base` · `$color-orange-base` | `#03A8D5` · `#86BB24` · `#FF9829` | Acentos, gráficos, íconos y fondos de etiqueta (con texto negro: 6,5 a 8,4:1) |
| `$color-blue-strong` · `$color-green-strong` · `$color-orange-strong` | `#027695` · `#507016` · `#995B19` | Botones y texto de color (≥ 5:1 con blanco) |
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
| `blue`, `green`, `orange` | Color base | Negro |
| `neutral` | `#C9CED4` | Negro |
| `dark` | `#3D434A` | Blanco |
| `muted` | `#DDE5EA` | Gris secundario |
| `danger` | `#C93A3A` | Blanco |

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

Escala de 4 px (`$space-1` a `$space-7`: 4, 8, 12, 16, 24, 32 y 48). Radios de 8 px en controles, 10 px en tarjetas y redondo completo en etiquetas. El área táctil mínima es de 44 px (`$touch-target`).

## Conexión con Quasar

`src/design/theme/quasar-variables.scss` asigna los tokens a las variables de Quasar (`$primary`, `$negative`, tipografía, radios y breakpoints). No define valores propios: si cambia un token, Quasar cambia con él. `global.scss` aplica la fuente, el fondo, el foco visible y la preferencia de reducir movimiento.

## Dónde está en el código

- [`frontend/src/design/`](../../../frontend/src/design/)
- [`frontend/stylelint.config.js`](../../../frontend/stylelint.config.js)
- Decisiones: [ADR-0027](../../decisiones/0027-diseno-por-tokens.md) y [ADR-0028](../../decisiones/0028-identidad-visual.md)
