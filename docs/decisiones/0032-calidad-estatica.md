---
title: "ADR-0032: Análisis estático, estilo y seguridad automatizada"
description: "Análisis estático, estilo y seguridad automatizada"
audience: tecnico
area: decisiones
order: 42
---

# ADR-0032: Análisis estático, estilo y seguridad automatizada

- **Estado:** Aceptada
- **Fecha:** 2026-10-31

## Contexto
La calidad del código y la seguridad deben verificarse en cada cambio, no solo en revisiones manuales.

## Alternativas evaluadas
- Revisión manual.
- Herramientas automáticas, en local y en el CI.

## Decisión
- **Backend:** Larastan en nivel 8 y Laravel Pint.
- **Frontend:**
  - ESLint, con reglas de seguridad y `v-html` prohibido.
  - vue-tsc y Prettier.
  - Stylelint, que impide usar valores de diseño fuera de los tokens.
- **Seguridad:** `composer audit`, `npm audit`, Gitleaks y OWASP ZAP (este último en un perfil opcional).
- **Antes de cada commit:** Lefthook ejecuta las revisiones rápidas y valida el formato del mensaje.

## Alternativas descartadas
- **Solo revisión manual:** es inconsistente y no escala.

## Consecuencias
- No llega al repositorio ningún cambio que rompa el estilo, los tipos o los tokens, ni que filtre un secreto.
