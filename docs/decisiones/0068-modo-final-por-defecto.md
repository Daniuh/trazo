---
title: "ADR-0068: El modo final es el que arranca por defecto"
description: "docker compose up -d levanta las imágenes finales (rápidas); el modo desarrollo pasa a un archivo propio y opcional"
audience: tecnico
area: decisiones
tags: [operacion]
order: 78
---

# ADR-0068: El modo final es el que arranca por defecto

- **Estado:** Aceptada. Reemplaza en parte al [ADR-0025](0025-docker.md) en la organización de los archivos de Compose.
- **Fecha:** 2026-10-31

## Contexto
Hasta ahora, `docker compose up -d` cargaba solo el `docker-compose.override.yml`, que era el modo desarrollo: el código montado desde la carpeta del equipo, Vite sin empaquetar y PHP revisando si cambió algún archivo en cada petición.

En Windows y macOS la carpeta montada es lenta para Docker. Se midió así:

- La pantalla de ingreso pedía 84 archivos sueltos y tardaba unos 3,6 s.
- Cada petición a la API tardaba de 0,4 a 1,5 s.

Ese es el comando del README, y quien evalúe la prueba en Windows habría visto una app lenta que no refleja la real.

## Alternativas evaluadas
- Que `docker compose up -d` levante las imágenes finales y que el modo desarrollo pase a un archivo opcional.
- Mantener el modo desarrollo por defecto y levantar las imágenes finales a mano solo para revisar.
- Afinar el modo desarrollo (que PHP revise menos seguido los archivos).

## Decisión
- **`docker-compose.yml`** (base, sin cambios): las imágenes finales. Es el build de Vue servido por Nginx con la CSP estricta, y la API con el código y las dependencias dentro de la imagen, sin revisar archivos.
- **`docker-compose.override.yml`** (se carga solo): solo los extras locales que no cambian lo que ve quien usa Trazo. Mailpit y los perfiles opcionales `zap` y `docs`.
- **`docker-compose.dev.yml`** (nuevo, opcional): el modo desarrollo de antes, con el código montado, Vite con recarga en caliente, Composer, Pest, Pint, Larastan y PCOV. Se activa con:
  ```bash
  docker compose -f docker-compose.yml -f docker-compose.override.yml -f docker-compose.dev.yml up -d
  ```
  También se puede dejar fijo en un `.env` local con `COMPOSE_FILE`.
- **Datos de demostración sin Faker:** la imagen final no trae las dependencias de desarrollo, y Faker es una de ellas. Los seeders pasan a usar el generador de PHP (`Random\Randomizer` con semilla fija) en `DemoRandom`. Los datos siguen siendo los mismos en cada máquina y Faker queda solo para las factories de las pruebas.
- **CI:** el trabajo del backend usa el modo desarrollo, porque necesita Pest, Larastan y PCOV. La E2E corre contra el modo final, con el mismo build y la misma CSP que verá quien use Trazo.
- **Gancho de Pint:** corre en un contenedor de un solo uso con la imagen de desarrollo. Así funciona sea cual sea el modo del stack.

## Alternativas descartadas
- **Modo final solo para revisar:** quien evalúe seguiría viendo la versión lenta con el comando del README. Además, cambiar de modo a mano cada vez es fácil de olvidar.
- **Afinar el modo desarrollo:** mejora algo la API, pero no la cantidad de archivos que pide el navegador. El problema de fondo es leer el código desde la carpeta del equipo, y eso no cambia.
- **Mover Faker a las dependencias de producción:** resuelve los seeders, pero mete una librería de desarrollo en la imagen final. El generador de PHP hace lo mismo sin dependencias.

## Consecuencias
- `docker compose up -d` muestra la velocidad real. Medido en local (Windows, Docker Desktop), con los mismos datos:

  | | Modo desarrollo | Modo final |
  |---|---|---|
  | Pantalla de ingreso (carga completa) | ~3,6 s, 84 archivos | ~0,13 s, 29 archivos |
  | Petición a la API (sesión, catálogos, dashboard, listado) | 0,4–1,5 s | 20–80 ms |
- Un cambio en el código no se ve en el modo final hasta reconstruir (`docker compose up -d --build`). Para trabajar en el código se usa el modo desarrollo.
- La guía visual solo existe en el modo desarrollo. La E2E la revisa solo si el stack corre en ese modo.
- `composer check`, `composer coverage` y los comandos de prueba del back necesitan el modo desarrollo.
- El primer arranque construye las imágenes finales, y el build del front tarda unos minutos más que antes.

## Dónde está en el código
- [`docker-compose.yml`](../../docker-compose.yml), [`docker-compose.override.yml`](../../docker-compose.override.yml) y [`docker-compose.dev.yml`](../../docker-compose.dev.yml).
- [`backend/database/seeders/Support/DemoRandom.php`](../../backend/database/seeders/Support/DemoRandom.php).
- [`.github/workflows/ci.yml`](../../.github/workflows/ci.yml): `COMPOSE_FILE` del trabajo del backend.
- [`lefthook.yml`](../../lefthook.yml): Pint en un contenedor de un solo uso.
- [`frontend/e2e/accessibility.spec.ts`](../../frontend/e2e/accessibility.spec.ts): la guía visual solo en el modo desarrollo.
