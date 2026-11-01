---
title: "ADR-0025: Contenedores: gateway de origen único y una sola imagen de Laravel"
description: "Contenedores: gateway de origen único y una sola imagen de Laravel"
audience: tecnico
area: decisiones
tags: [operacion]
order: 35
---

# ADR-0025: Contenedores: gateway de origen único y una sola imagen de Laravel

- **Estado:** Aceptada
- **Fecha:** 2026-10-31

## Contexto
Todo el proyecto debe ejecutarse con Docker: Laravel, Nginx, la base de datos y el frontend. Idealmente, con un solo comando. Además, en la misma máquina pueden convivir otros proyectos.

## Alternativas evaluadas
- Exponer cada servicio en su propio puerto.
- Un gateway Nginx como única entrada.

## Decisión
**Servicios:**

| Servicio | Función |
|---|---|
| `gateway` | Nginx que enruta `/api`, `/` y `/ws` |
| `app` | PHP-FPM con la API |
| `frontend` | Build de Vue servido por Nginx |
| `mysql`, `redis` | Base de datos y caché/colas |
| `worker`, `scheduler`, `reverb` | Colas, tareas programadas y tiempo real |
| `mailpit` | Correo, solo en local |
| `zap` | Escaneo de seguridad, en un perfil opcional |

**Construcción y arranque:**
- Una sola imagen de Laravel sirve a `app`, `worker`, `scheduler` y `reverb`.
- Un servicio de inicialización ejecuta las migraciones y los seeders idempotentes.
- Todos los servicios tienen healthchecks.
- Los contenedores no corren como root, usan versiones fijas y se construyen en varias etapas.
- Hay tres archivos de compose: base, override local y producción.

**Convivencia con otros proyectos:**
- Nombre de proyecto exclusivo `trazo`.
- Redes y volúmenes propios.
- Un único puerto publicado, configurable.
- Límites de recursos por contenedor.

## Alternativas descartadas
- **Un puerto por servicio:** obliga a configurar CORS, aumenta la superficie expuesta y facilita los choques con otros proyectos.

## Consecuencias
- MySQL y Redis nunca se exponen fuera de la red interna.
- El front y la API comparten origen, así que no hace falta CORS.
