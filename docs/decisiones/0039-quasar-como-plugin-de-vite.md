---
title: "ADR-0039: Quasar integrado como plugin de Vite"
description: "Quasar integrado como plugin de Vite"
audience: tecnico
area: decisiones
order: 49
---

# ADR-0039: Quasar integrado como plugin de Vite

- **Estado:** Aceptada
- **Fecha:** 2026-10-21

## Contexto
Quasar se puede usar de dos maneras oficiales, y la elección define quién controla la estructura del frontend. El proyecto ya tiene una estructura aprobada por módulos y capas (ADR-0003).

## Alternativas evaluadas
- Quasar CLI (`@quasar/app-vite`), que gestiona el proyecto completo.
- Plugin oficial de Vite (`@quasar/vite-plugin`) sobre un proyecto Vite estándar.

## Decisión
Plugin de Vite. Quasar aporta componentes, plugins y tema; la estructura (`app/`, `modules/`, `shared/`, `design/`) la define el proyecto. Las variables de Quasar se alimentan de los tokens de diseño.

## Alternativas descartadas
- **Quasar CLI:** impone su propia estructura (`boot/`, `pages/`, `layouts/`, `quasar.config`) que choca con los módulos por funcionalidad, y sus ventajas principales (empaquetar como app móvil o de escritorio, SSR) no se usan.

## Consecuencias
- Configuración estándar de Vite, más simple de mantener y de probar con Vitest.
- Las actualizaciones de Quasar siguen siendo oficiales: ambas vías tienen soporte.

## Dónde está en el código
- [`frontend/vite.config.ts`](../../frontend/vite.config.ts)
- [`frontend/src/app/plugins/quasar.ts`](../../frontend/src/app/plugins/quasar.ts)
