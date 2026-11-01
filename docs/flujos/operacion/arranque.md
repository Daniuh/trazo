---
title: Arranque del stack (init)
description: El orden en que se levantan los servicios de Docker con docker compose up -d, quién espera a quién y qué hace el servicio de inicialización.
audience: tecnico
area: flujos
module: operacion
order: 1
---

# Arranque del stack (init)

`docker compose up -d` levanta Trazo completo, con el nombre de proyecto propio `trazo`. Cada servicio espera a que el anterior esté sano (healthcheck) o haya terminado bien, así nada arranca contra una base que todavía no responde. Las migraciones y los seeders son idempotentes: el servicio `init` los corre en cada arranque y no duplica nada.

```flujo
carriles: [Docker, MySQL y Redis, init, App y procesos, Gateway]
pasos:
  - id: up
    carril: Docker
    tipo: inicio
    texto: docker compose up -d
    detalle: 'Por defecto, imágenes finales (lo que verán los clientes) más Mailpit para el correo local. El modo desarrollo se agrega con `docker-compose.dev.yml`.'
    va: datos
  - id: datos
    carril: MySQL y Redis
    tipo: decision
    texto: ¿MySQL y Redis están sanos?
    detalle: 'MySQL 8.4 y Redis 8.4 con sus healthchecks. La configuración de MySQL va como parámetros del `command`, porque desde Windows ignora los `.cnf` montados.'
    si: llave
    no: espera
  - id: espera
    carril: Docker
    tipo: error
    texto: Espera y reintenta el healthcheck
  - id: llave
    carril: init
    tipo: decision
    texto: ¿Hay APP_KEY?
    detalle: 'Si no llega por el entorno, la lee de `/run/trazo/app-key` (volumen persistente).'
    si: migra
    no: genera
  - id: genera
    carril: init
    tipo: decision
    texto: ¿Es producción?
    detalle: En producción la llave se define a mano y se respalda; generarla sola rompería los datos cifrados al recrear el volumen.
    si: sinllave
    no: crea
  - id: sinllave
    carril: init
    tipo: error
    texto: Se detiene con "Falta APP_KEY"
  - id: crea
    carril: init
    texto: Genera la llave local en el volumen
    va: migra
  - id: migra
    carril: init
    texto: migrate --force --seed
    detalle: 'Migraciones solo aditivas y seeders idempotentes: catálogos por clave natural, solo lo que falta. Los usuarios demo `@trazo.test` no se crean en producción; las cuentas de evaluación salen del entorno.'
    va: ok
  - id: ok
    carril: init
    tipo: decision
    texto: ¿Terminó bien?
    si: procesos
    no: falla
  - id: falla
    carril: Docker
    tipo: error
    texto: Nada más arranca; el error queda en sus logs
  - id: procesos
    carril: App y procesos
    texto: Arrancan app, worker, scheduler y Reverb
    detalle: 'Una sola imagen de Laravel para los cuatro, sin root y con límites de CPU y memoria. El servicio `backup` arranca aparte, en cuanto MySQL está sano.'
    va: sano
  - id: sano
    carril: App y procesos
    tipo: decision
    texto: ¿app y frontend están sanos?
    si: gateway
    no: espera2
  - id: espera2
    carril: Docker
    tipo: error
    texto: El gateway no arranca hasta que lo estén
  - id: gateway
    carril: Gateway
    tipo: fin
    texto: Abre el puerto 8088
    detalle: 'Único puerto publicado (`TRAZO_HTTP_PORT`). Sirve `/` (el front), `/api` (Laravel) y `/ws` (Reverb). Resuelve los servicios por DNS en cada petición, para que un contenedor recreado no deje un 502.'
```

## Qué hacer si algo falla

| Síntoma | Dónde mirar |
|---|---|
| `init` sale con error | `docker compose -p trazo logs init`: casi siempre una migración o una variable faltante |
| El gateway no arranca | `docker compose -p trazo ps`: qué servicio sigue sin estar sano |
| "Falta APP_KEY" en producción | Definir `APP_KEY` en el `.env` del servidor |

Nunca se usan comandos globales de Docker (`system prune`, `volume prune`…): en la misma máquina hay otros proyectos.

## Dónde está en el código

- [`docker-compose.yml`](../../../docker-compose.yml): servicios, dependencias, healthchecks y límites.
- [`entrypoint.sh`](../../../docker/php/entrypoint.sh): la llave de la aplicación.
- [`DatabaseSeeder`](../../../backend/database/seeders/DatabaseSeeder.php): qué siembra y cuándo.
- [`gateway.conf`](../../../docker/nginx/gateway.conf): las tres rutas del gateway.
- Explicación: [Docker](../../operacion/docker.md).
