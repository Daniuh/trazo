---
title: Responsive
description: Las cuatro vistas de Trazo y cómo se escriben los estilos de cada una.
audience: tecnico
area: frontend
module: design
order: 11
---

# Responsive

Todo lo que se construye debe funcionar en las cuatro vistas:

| Vista | Ancho | Mixin |
|---|---|---|
| Celular | 320 – 599 px | (estilo base) |
| Tablet | 600 – 1023 px | `@include tablet` |
| Portátil | 1024 – 1439 px | `@include laptop` |
| Monitor | 1440 px o más | `@include desktop` |

## Cómo se escribe

*Mobile first*: el estilo base es el del celular y cada bloque agrega solo lo que cambia. Un solo archivo de estilos por componente, con un bloque por vista:

```scss
@use '@/design/tokens' as *;

.incident-card { /* celular */ }

@include tablet  { .incident-card { /* tablet */ } }
@include laptop  { .incident-card { /* portátil */ } }
@include desktop { .incident-card { /* monitor */ } }
```

Cuando la estructura cambia por completo (por ejemplo, tarjetas en celular y tabla en portátil), se usan dos componentes visuales que comparten la misma lógica de la capa `application`.

`@media` escrito a mano está prohibido fuera de `design/`: si una vista necesita un ajuste especial, se toca su bloque o su token.

## Dónde está en el código

- [`frontend/src/design/tokens/breakpoints.scss`](../../../frontend/src/design/tokens/breakpoints.scss)
- Decisión: [ADR-0027](../../decisiones/0027-diseno-por-tokens.md)
