---
title: Tokens de diseño
description: Variables de diseño de Trazo y cómo se conectan con Quasar.
audience: tecnico
area: frontend
module: design
order: 10
---

# Tokens de diseño

Todo valor visual vive en `src/design/tokens/`, un archivo por categoría. Los componentes solo usan tokens y mixins; Stylelint rechaza colores, funciones de color y `@media` escritos fuera de `design/`.

| Archivo | Contenido | Estado |
|---|---|---|
| `colors.scss` | Paleta de la "o" de Psicol, tonos oscuros para texto y botones, roles | Base lista; falta el rojo de error |
| `breakpoints.scss` | Las 4 vistas y sus mixins | Listo |
| `typography.scss`, `spacing.scss`, `shadows.scss` | Tipografía (Inter), espaciado y sombras | Con el sistema de diseño |

## Colores

| Token | Valor | Uso |
|---|---|---|
| `$color-blue-base` | `#03A8D5` | Acentos, gráficos, íconos |
| `$color-green-base` | `#86BB24` | Acentos, gráficos, íconos |
| `$color-orange-base` | `#FF9829` | Acentos, gráficos, íconos |
| `$color-blue-strong` | `#027695` | Botones y texto (contraste 5,2:1 con blanco) |
| `$color-green-strong` | `#507016` | Botones y texto (5,7:1) |
| `$color-orange-strong` | `#995B19` | Botones y texto (5,4:1) |
| `$color-text` | `#161616` | Texto principal |

Los colores base no alcanzan contraste AA con texto blanco, por eso nunca se usan como fondo de un texto.

## Conexión con Quasar

`src/design/theme/quasar-variables.scss` asigna los tokens a las variables de Quasar (`$primary`, `$secondary`, breakpoints…). No define valores propios: si cambia un token, Quasar cambia con él.

## Dónde está en el código

- [`frontend/src/design/`](../../../frontend/src/design/)
- [`frontend/stylelint.config.js`](../../../frontend/stylelint.config.js)
- Decisiones: [ADR-0027](../../decisiones/0027-diseno-por-tokens.md) y [ADR-0028](../../decisiones/0028-identidad-visual.md)
