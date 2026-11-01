---
title: "ADR-0066: Llave de la app sin depender del entrypoint"
description: "La configuración lee la APP_KEY del volumen local cuando no llega por el entorno, para que docker compose exec funcione"
audience: tecnico
area: decisiones
tags: [seguridad]
order: 76
---

# ADR-0066: Llave de la app sin depender del entrypoint

- **Estado:** Aceptada
- **Fecha:** 2026-10-31

## Contexto
En local, la `APP_KEY` se genera una vez y se guarda en el volumen `trazo_app-key` ([ADR-0025](0025-docker.md)). El entrypoint de la imagen la lee y la exporta antes de arrancar cada servicio. Pero `docker compose exec app php artisan ...` no pasa por el entrypoint, así que ese comando corría sin llave. Funcionaba lo que no cifra, y fallaba lo que sí: descifrar un secreto del MFA, encolar un correo cifrado o probar algo en `tinker`.

## Alternativas evaluadas
- Que `config/app.php` lea la llave del archivo del volumen cuando no llega por el entorno.
- Documentar que los comandos vayan precedidos de `trazo-entrypoint`.
- Escribir la llave en `backend/.env`.

## Decisión
`config/app.php` usa `APP_KEY` si llega por el entorno. Si no, lee el archivo `APP_KEY_FILE` (por defecto `/run/trazo/app-key`, el volumen). Así cualquier comando tiene la llave, entre o no por el entrypoint, y se resuelve en un solo lugar.

- **Producción no cambia:** ahí la llave siempre llega por el entorno, y el entrypoint se niega a arrancar si falta.
- **Respeta la regla de arquitectura:** el entorno y el archivo se leen solo en `config/`.

## Alternativas descartadas
- **El prefijo `trazo-entrypoint`:** funciona, pero es fácil olvidarlo. El error aparece lejos de la causa, por ejemplo en un correo que el worker no puede leer.
- **Escribir la llave en `backend/.env`:** deja un secreto en un archivo de la carpeta de trabajo, al alcance de un `git add` descuidado o de una copia del proyecto. Gitleaks lo detendría, pero es mejor no crear el riesgo.

## Consecuencias
- `docker compose exec app php artisan tinker` y cualquier comando de Laravel funcionan igual que la app.
- Las pruebas no cambian: `TestCase` genera su propia llave al azar.

## Dónde está en el código
- [`backend/config/app.php`](../../backend/config/app.php): clave `key`.
- [`docker/php/entrypoint.sh`](../../docker/php/entrypoint.sh): genera la llave local.
- Guía: [Docker → Llave de cifrado](../operacion/docker.md#llave-de-cifrado-app_key).
