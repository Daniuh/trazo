---
title: "ADR-0065: Documentación de funciones"
description: "Documentación de funciones: phpDocumentor en su imagen oficial y TypeDoc como dependencia de desarrollo, generadas bajo demanda y en el CI"
audience: tecnico
area: decisiones
tags: [operacion]
order: 75
---

# ADR-0065: Documentación de funciones

- **Estado:** Aceptada
- **Fecha:** 2026-10-31

## Contexto
La documentación en Markdown llega hasta el submódulo y el caso de uso. El nivel de función se documenta con PHPDoc y TSDoc en el código, y estaba aprobado generarlo con phpDocumentor y TypeDoc. Faltaba configurarlo, y también decidir cuándo se genera y dónde queda el resultado.

## Alternativas evaluadas
Para phpDocumentor:
- La imagen oficial `phpdoc/phpdoc` como servicio de Docker.
- Instalarlo con Composer como dependencia de desarrollo.
- Descargar el `.phar`.

Para el resultado:
- Generarlo bajo demanda y en el CI, sin subirlo al repositorio.
- Subir el sitio generado al repositorio.

## Decisión
- **phpDocumentor 3.10.0** con su imagen oficial, como servicio `phpdoc` del perfil opcional `docs` (igual que ZAP): no arranca con `docker compose up -d`. Corre sin red, lee `backend/app` en solo lectura y usa `backend/phpdoc.dist.xml`.
- **TypeDoc 0.28.20** como dependencia de desarrollo del front (compatible con TypeScript 6.0). Documenta los `.ts` de `shared` y de las capas `domain`, `application` e `infrastructure` de cada módulo, sin las pruebas. Las pantallas `.vue` no entran: su contrato son las props y los eventos, que ya se revisan con `vue-tsc`.
- **TypeDoc compila con `tsc`**, que no entiende los `.vue`. Por eso usa un `tsconfig.docs.json` propio, con una declaración que trata los `.vue` como componentes genéricos. `vue-tsc` no la usa, así que los tipos reales de cada componente se siguen revisando.
- **Salida en `docs-build/`** (`backend/` y `frontend/`), ignorada por Git. Es la materia prima de la futura web de documentación.
- **CI:** el trabajo "Documentación de funciones" genera los dos sitios y los deja como artefacto por 14 días. Falla si TypeDoc encuentra un enlace roto o un aviso, o si phpDocumentor reporta errores en un PHPDoc.
- **Comandos:** `docker compose --profile docs run --rm phpdoc` y `npm run docs:ts`.

## Alternativas descartadas
- **phpDocumentor por Composer:** sus dependencias (Symfony, Twig, analizadores de tipos) chocan con las de Laravel 13, y además engordarían `vendor`.
- **El `.phar`:** hay que descargarlo, verificar su firma y fijar su versión a mano. La imagen oficial con etiqueta exacta resuelve las tres cosas.
- **Subir el sitio generado:** son miles de archivos que cambian con cada commit, ensucian los diffs y se desactualizan si alguien olvida regenerarlos.

## Consecuencias
- Un PHPDoc o un TSDoc roto se detecta en el PR, no cuando alguien abre la documentación.
- Los enlaces al código fuente desde TypeDoc quedan apagados mientras el repositorio no tenga remoto. Se activan al publicarlo.
- phpDocumentor tarda cerca de un minuto y medio en local (Docker sobre Windows) y menos en el CI.

## Dónde está en el código
- [`backend/phpdoc.dist.xml`](../../backend/phpdoc.dist.xml) y el servicio `phpdoc` en [`docker-compose.override.yml`](../../docker-compose.override.yml).
- [`frontend/typedoc.json`](../../frontend/typedoc.json), [`tsconfig.docs.json`](../../frontend/tsconfig.docs.json) y [`typedoc/vue-shim.d.ts`](../../frontend/typedoc/vue-shim.d.ts).
- Trabajo `api-docs` en [`.github/workflows/ci.yml`](../../.github/workflows/ci.yml).
