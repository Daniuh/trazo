---
title: "ADR-0038: Versiones del stack"
description: "Versiones del stack"
audience: tecnico
area: decisiones
order: 48
---

# ADR-0038: Versiones del stack

- **Estado:** Aceptada
- **Fecha:** 2026-10-21

## Contexto
La prueba exige PHP 8+ y Laravel 10+, así que cualquier versión posterior cumple. Lo que hay que evitar es construir sobre una versión que ya no recibe parches de seguridad.

## Alternativas evaluadas

| Componente | Versión | Fin de soporte de seguridad |
|---|---|---|
| Laravel 11 | 11.57 | Marzo de 2026 (vencido) |
| Laravel 12 | 12.70 | Febrero de 2027, aproximadamente |
| Laravel 13 | 13.x | Marzo de 2028, aproximadamente |
| PHP 8.3 | 8.3.35 | Diciembre de 2027 |
| PHP 8.4 | 8.4.26 | Diciembre de 2028 |
| PHP 8.5 | 8.5.11 | Diciembre de 2029 |

## Decisión
- **Backend:** Laravel 13 sobre PHP 8.4.
- **Frontend:** Node 24 LTS, Vue 3.5, Vite y TypeScript, en las versiones que soporta Quasar.
- **Servicios:**
  - MySQL 8.4 LTS
  - Redis 8.4
  - Nginx 1.30, rama estable, en su variante sin root
  - Mailpit 1.31
- **Imágenes:** todas con versión exacta.

## Alternativas descartadas
- **Laravel 11:** ya no recibe parches de seguridad.
- **Laravel 12:** su soporte termina en pocos meses.
- **PHP 8.5:** es la versión más reciente, y parte del ecosistema todavía no la soporta del todo. PHP 8.4 tiene soporte hasta 2028 y es compatible con todas las librerías del proyecto (Sanctum 4, Reverb, Pest 4, Larastan 3 y Scramble).

## Consecuencias
- El proyecto arranca sobre versiones con soporte por al menos dos años.
- Dependabot avisará de las actualizaciones de seguridad dentro de cada versión.

## Dónde está en el código
- [`docker/php/Dockerfile`](../../docker/php/Dockerfile) y [`docker/frontend/Dockerfile`](../../docker/frontend/Dockerfile)
- [`docker-compose.yml`](../../docker-compose.yml)
- [`backend/composer.json`](../../backend/composer.json) y [`frontend/package.json`](../../frontend/package.json)
