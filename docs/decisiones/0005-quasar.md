---
title: "ADR-0005: Quasar como librería de interfaz"
description: "Quasar como librería de interfaz"
audience: tecnico
area: decisiones
order: 15
---

# ADR-0005: Quasar como librería de interfaz

- **Estado:** Aceptada
- **Fecha:** 2026-10-21

## Contexto
La prueba sugiere Quasar o Vuetify. Se necesita una tabla con paginación del lado del servidor, una grilla responsive sólida y un tema que se alimente de tokens de diseño.

## Alternativas evaluadas
- Quasar.
- Vuetify.

## Decisión
Quasar, por tres razones:

- Su tabla soporta paginación, orden y filtros del lado del servidor sin adaptaciones.
- Sus breakpoints coinciden con los definidos para el producto.
- Su tema se configura con variables, lo que encaja con el diseño por tokens.

## Alternativas descartadas
- **Vuetify:** es igual de capaz, pero su sistema de temas está más atado a Material Design. Ajustarlo a una identidad propia por tokens exige más sobrescrituras.

## Consecuencias
- Quasar queda confinado a la capa de presentación.
- Los componentes que más se reutilizan se envuelven en componentes propios (`AppButton`, `AppTable`…).
