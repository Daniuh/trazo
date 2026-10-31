---
title: Estrategia de pruebas
description: Qué se prueba, con qué herramientas y cuándo.
audience: tecnico
area: calidad
order: 2
---

# Estrategia de pruebas

## Backend

Las pruebas usan Pest y corren dentro del contenedor contra MySQL real, en la base `trazo_test`. Nunca se usa SQLite: FULLTEXT, las restricciones CHECK y las claves foráneas se comportan distinto y darían falsa confianza.

| Carpeta | Tipo | Qué cubre |
|---|---|---|
| `tests/Arch` | Arquitectura | Separación de capas por módulo, tipos estrictos, sin funciones de depuración ni lectura de entorno fuera de `config/` |
| `tests/Unit` | Unitarias | Reglas de dominio, sin base de datos |
| `tests/Feature` | API | Endpoints completos: respuestas, errores, permisos y consultas |

Una prueba de entorno garantiza que las pruebas corren en `testing` y contra `trazo_test`, para que nunca toquen la base de desarrollo. Las variables de pruebas se fuerzan en `phpunit.xml` porque, dentro del contenedor, las variables del propio contenedor tendrían prioridad.

### Análisis estático y formato

- **Larastan** en nivel 8 sobre `app/`, `bootstrap/app.php` y `routes/`.
- **Pint** con el preset de Laravel más tipos estrictos y comparaciones estrictas.

### Cuándo se ejecuta

| Momento | Qué corre |
|---|---|
| Antes de cada commit | Formato (Pint) sobre los archivos PHP modificados |
| Antes de fusionar a `dev` | `composer check`: formato, análisis estático y todas las pruebas |
| En el CI (cuando el repositorio esté publicado) | Todo lo anterior en cada Pull Request |

## Frontend

| Herramienta | Qué revisa |
|---|---|
| **Vitest** | Pruebas unitarias y de componentes, junto a cada archivo (`*.spec.ts`) |
| **vue-tsc** | Tipos en TypeScript estricto (incluye `noUncheckedIndexedAccess`) |
| **ESLint** | Reglas de Vue y TypeScript; `v-html` y `eval` prohibidos |
| **Stylelint** | Ningún color, función de color ni `@media` fuera de `design/` |
| **Prettier** | Formato uniforme |

`npm run check` ejecuta tipos, lint y pruebas. Antes de cada commit, Lefthook corre ESLint, Prettier y Stylelint sobre los archivos modificados. Las pruebas de extremo a extremo (Playwright en las cuatro vistas) se agregan con las primeras pantallas.

## Dónde está en el código

- [`backend/tests/`](../../backend/tests/) y [`backend/phpunit.xml`](../../backend/phpunit.xml)
- [`backend/phpstan.neon`](../../backend/phpstan.neon) y [`backend/pint.json`](../../backend/pint.json)
- [`frontend/eslint.config.js`](../../frontend/eslint.config.js) y [`frontend/stylelint.config.js`](../../frontend/stylelint.config.js)
- [`lefthook.yml`](../../lefthook.yml)
- Decisiones: [ADR-0031](../decisiones/0031-estrategia-de-pruebas.md) y [ADR-0032](../decisiones/0032-calidad-estatica.md).
