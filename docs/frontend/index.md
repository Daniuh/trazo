---
title: Frontend
description: Estructura de la SPA en Vue 3, TypeScript y Quasar, organizada por módulos.
audience: tecnico
area: frontend
order: 1
---

# Frontend

SPA en Vue 3 con TypeScript estricto y Quasar (integrado como plugin de Vite). Sigue la misma idea que el backend: cada funcionalidad es un módulo con sus capas, y la lógica no depende de la librería de interfaz.

## Estructura

```
frontend/src/
├── main.ts              → punto de entrada: delega en app/bootstrap
├── app/                 → arranque, router, plugins y páginas de la aplicación
│   ├── bootstrap.ts
│   ├── plugins/quasar.ts
│   ├── router/          → reúne las rutas de todos los módulos
│   ├── layout/          → estructura común (encabezado, menú, contenido)
│   ├── navigation.ts    → reúne el menú de todos los módulos
│   └── pages/           → páginas globales (404 y guía visual en desarrollo)
├── modules/<módulo>/    → una carpeta por funcionalidad (hoy: auth, incidents, dashboard, admin, audit, realtime y system)
│   ├── domain/          → tipos y reglas puras
│   ├── application/     → casos de uso (composables y stores)
│   ├── infrastructure/  → repositorios de la API y mappers
│   ├── presentation/    → páginas y componentes del módulo
│   ├── routes.ts        → rutas del módulo (se registran solas, con carga diferida)
│   ├── navigation.ts    → entradas del menú, con el permiso que piden
│   └── errors.ts        → mensajes de sus códigos de error (se registran solos)
├── shared/              → lo transversal
│   ├── format/          → fechas para las personas (completas y relativas)
│   ├── listing/         → listados ligados a la URL (useRouteList)
│   ├── routing/         → lectura tolerante de la URL (queryValues)
│   ├── validation/      → revisiones visuales compartidas (forma de un correo)
│   ├── http/            → cliente HTTP único, errores normalizados, token de sesión
│   ├── errors/          → catálogo de mensajes de error
│   └── ui/              → componentes base reutilizables
└── design/              → tokens, tema, íconos y marca
```

## Reglas

- **Las vistas nunca llaman a la API.** Usan la capa `application` de su módulo, que usa los repositorios de `infrastructure`.
- **Quasar queda en la presentación.** Dominio y aplicación no lo importan.
- **Ningún valor de diseño escrito a mano** fuera de `design/`: lo impide Stylelint.
- **`v-html` está prohibido** (ESLint), para cerrar la puerta a XSS.
- **El front no calcula reglas de negocio:** muestra u oculta acciones según lo que indica la API en `actions`.

## Comandos

Desde `frontend/` (o dentro del contenedor):

| Comando | Qué hace |
|---|---|
| `npm run dev` | Servidor de desarrollo (en Docker ya corre detrás del gateway) |
| `npm run build` | Verifica tipos y compila |
| `npm run typecheck` | Verificación de tipos con vue-tsc |
| `npm run lint` | ESLint, Stylelint y Prettier |
| `npm run format` | Corrige formato |
| `npm test` | Pruebas con Vitest |
| `npm run check` | Tipos, lint y pruebas; debe pasar antes de fusionar a `dev` |
| `npm run docs:ts` | Documentación de funciones con TypeDoc, en `docs-build/frontend/` (fuera de Git) |

TypeDoc documenta los `.ts` de `shared` y de las capas `domain`, `application` e `infrastructure` de cada módulo. Compila con su propio `tsconfig.docs.json`, que trata los `.vue` como componentes genéricos porque `tsc` no los entiende ([ADR-0065](../decisiones/0065-documentacion-de-funciones.md)).

## Documentos relacionados

- [Cliente HTTP y errores](http-y-errores.md)
- [Módulo de sesión](auth.md)
- [Módulo de incidentes](incidents.md)
- [Módulo de dashboard](dashboard.md)
- Sistema de diseño: [tokens](sistema-de-diseno/tokens.md), [responsive](sistema-de-diseno/responsive.md), [componentes](sistema-de-diseno/componentes.md), [íconos](sistema-de-diseno/iconos.md), [marca](sistema-de-diseno/marca.md) y [layout](sistema-de-diseno/layout.md)
- Decisiones: [ADR-0003](../decisiones/0003-frontend-modular.md), [ADR-0039](../decisiones/0039-quasar-como-plugin-de-vite.md) y [ADR-0040](../decisiones/0040-axios.md)

## Dónde está en el código

- [`frontend/src/app/`](../../frontend/src/app/) y [`frontend/src/shared/`](../../frontend/src/shared/)
- [`frontend/vite.config.ts`](../../frontend/vite.config.ts), [`eslint.config.js`](../../frontend/eslint.config.js) y [`stylelint.config.js`](../../frontend/stylelint.config.js)
- [`frontend/typedoc.json`](../../frontend/typedoc.json), [`tsconfig.docs.json`](../../frontend/tsconfig.docs.json) y [`typedoc/vue-shim.d.ts`](../../frontend/typedoc/vue-shim.d.ts): documentación de funciones
