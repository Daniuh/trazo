---
title: "ADR-0092: Contenedores sin capacidades de Linux"
description: "Todos los servicios corren con no-new-privileges y sin capacidades; los que arrancan como root reciben solo las que necesitan para pasar a su usuario"
audience: tecnico
area: decisiones
tags: [seguridad, docker]
order: 102
---

# ADR-0092: Contenedores sin capacidades de Linux

- **Estado:** Aceptada.
- **Fecha:** 2026-11-03

## Contexto
Los contenedores ya corrían con usuarios sin privilegios, en redes separadas y sin acceso a `docker.sock`. Aun así, Docker les da por defecto un conjunto de capacidades de Linux (cambiar dueños de archivos, cambiar de usuario, abrir puertos bajos, entre otras) y permite que un proceso gane privilegios al ejecutar un programa con el bit setuid. Si alguien lograra ejecutar código dentro de un contenedor, esas capacidades serían su punto de partida para escalar.

## Alternativas evaluadas
- Dejar las capacidades por defecto de Docker.
- Quitar todas las capacidades y activar `no-new-privileges` en cada servicio, devolviendo solo las necesarias a los que arrancan como root.
- Además, montar el sistema de archivos de cada contenedor en solo lectura.

## Decisión
Todos los servicios llevan `security_opt: ["no-new-privileges:true"]` y `cap_drop: [ALL]`, desde dos anclas del compose base (`x-hardened` y `x-hardened-root-start`).

MySQL, Redis y el respaldo usan las imágenes oficiales, que arrancan como root para ajustar los permisos de sus carpetas y luego pasan a su usuario (`gosu`, `su-exec`). Reciben solo `CHOWN`, `DAC_OVERRIDE`, `FOWNER`, `SETUID` y `SETGID`. Al pasar a su usuario, el proceso que queda corriendo no conserva ninguna. En el modo desarrollo, `init` corre como root para ajustar el código montado y recibe las mismas cinco.

Verificado en un arranque desde cero y en un reinicio con datos: los servicios sanos, ingreso, correo por la cola, respaldo, tiempo real y tareas programadas funcionan, y el proceso principal de la API y de MySQL muestra `CapEff: 0` y `NoNewPrivs: 1`.

## Alternativas descartadas
- **Capacidades por defecto:** no hay razón para dejar permisos que ningún servicio usa.
- **Solo lectura:** PHP, Nginx y MySQL escriben en carpetas temporales y de caché; habría que declarar cada una como `tmpfs` y probarlas todas. Queda como mejora propuesta.

## Consecuencias
- Un servicio nuevo hereda el endurecimiento si usa una de las anclas. Si necesita una capacidad, se agrega solo esa y se explica en el compose.
- Una imagen que requiera arrancar como root sin las cinco capacidades de arriba fallará al arrancar: el error aparece en sus registros como `Operation not permitted`.

## Dónde está en el código
- [`docker-compose.yml`](../../docker-compose.yml) (anclas `x-hardened` y `x-hardened-root-start`), [`docker-compose.override.yml`](../../docker-compose.override.yml) y [`docker-compose.dev.yml`](../../docker-compose.dev.yml).
