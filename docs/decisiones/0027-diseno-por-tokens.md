---
title: "ADR-0027: Diseño por tokens, mobile first y estilos por bloque de vista"
description: "Diseño por tokens, mobile first y estilos por bloque de vista"
audience: tecnico
area: decisiones
order: 37
---

# ADR-0027: Diseño por tokens, mobile first y estilos por bloque de vista

- **Estado:** Aceptada
- **Fecha:** 2026-10-21

## Contexto
El diseño debe vivir separado del código, sin valores escritos a mano. Además, tiene que adaptarse a celular, tablet, portátil y monitor, con control independiente de cada vista.

## Alternativas evaluadas
- Estilos en cada componente con valores escritos a mano.
- Un archivo de estilos por vista y por componente.
- Tokens centralizados, con un archivo de estilos por componente y un bloque por vista.

## Decisión
- **Tokens:** viven en `design/tokens/`, con un archivo por categoría (colores, tipografía, espaciado, breakpoints y sombras), y se conectan a Quasar en `design/theme/`.
- **Breakpoints:**

  | Vista | Ancho |
  |---|---|
  | Celular | 320–599 px |
  | Tablet | 600–1023 px |
  | Portátil | 1024–1439 px |
  | Monitor | 1440 px o más |

- **Estilos de cada componente:** un solo archivo, *mobile first*, con un bloque por vista.
- **Cuando la estructura cambia por completo** (tarjetas en celular, tabla en portátil): se usan componentes visuales distintos que comparten la misma lógica.
- **Texto sobre fondos de color:** siempre negro o blanco, según cuál dé mejor contraste. Nunca un tono de la misma paleta.
- **Control automático:** Stylelint impide usar valores de diseño fuera de los tokens.

## Alternativas descartadas
- **Valores a mano:** cualquier cambio visual exige buscar y reemplazar en todo el proyecto.
- **Un archivo por vista:** cuadruplica los archivos y repite lo que las vistas tienen en común.

## Consecuencias
- Ajustar una vista es tocar un solo bloque.
- Ajustar la identidad es tocar los tokens.
