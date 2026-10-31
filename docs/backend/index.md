---
title: Backend
description: Módulos de la API en Laravel, organizados por dominio y por capa.
audience: tecnico
area: backend
order: 1
---

# Backend

La API es un monolito modular en Laravel 13 sobre PHP 8.4. Cada módulo agrupa un dominio del negocio y se divide en las capas Domain, Application, Infrastructure y Http (ver [Capas del backend](../arquitectura/capas.md)).

## Estructura

```
backend/
├── app/Modules/
│   ├── Audit/             → auditoría global de solo lectura (incidentes y administración)
│   ├── Auth/              → sesión: login, logout, bloqueo progresivo
│   ├── Dashboard/         → widgets, métricas en caché y diseño por persona
│   ├── Incidents/         → ciclo de vida de los incidentes y catálogos
│   ├── Realtime/          → avisos en tiempo real sin datos (Reverb) y autorización de canales
│   ├── System/            → salud, eventos del sistema y acciones del Super admin (sin Docker)
│   ├── Users/             → administración de personas, jerarquía y permisos efectivos
│   └── Shared/            → lo transversal: errores, identificador de petición, hora y transacciones
│       ├── Domain/
│       ├── Application/
│       ├── Infrastructure/
│       └── Http/
├── bootstrap/app.php      → rutas, middleware global y manejo de errores
├── routes/api.php         → carga las rutas de cada módulo bajo /api/v1
└── tests/
    ├── Arch/              → reglas de arquitectura
    ├── Unit/              → reglas de dominio, sin base de datos
    └── Feature/           → API completa contra MySQL (trazo_test)
```

## Módulos

| Módulo | Estado | Documento |
|---|---|---|
| Shared | Listo | [Shared](shared.md) |
| Auth | Login, sesión, MFA y recuperación y cambio de contraseña listos | [Auth](auth.md) |
| Incidents | CRUD, estados, asignación, historial y listado listos | [Incidents](incidents.md) |
| Dashboard | Widgets, métricas y diseño por persona listos | [Dashboard](dashboard.md) |
| Users | Administración de personas y de roles, jerarquía y permisos por persona listos | [Users](users.md) |
| Audit | Auditoría de incidentes y de administración lista | [Audit](audit.md) |
| Realtime | Canales, avisos de incidentes, de asignación y de sesión listos | [Realtime](realtime.md) |
| System | Salud, eventos agrupados y acciones del Super admin listos | [System](system.md) |

## Cómo se agrega un módulo

1. Crear `app/Modules/<Módulo>/` con las capas que necesite.
2. Declarar sus rutas en `app/Modules/<Módulo>/Http/routes.php`: se cargan solas bajo `/api/v1`.
3. Las pruebas de arquitectura lo incluyen automáticamente.

## Comandos de calidad

Se ejecutan dentro del contenedor (`docker compose exec app composer <comando>`):

| Comando | Qué hace |
|---|---|
| `composer test` | Pruebas con Pest |
| `composer lint` | Revisa el formato con Pint |
| `composer format` | Corrige el formato |
| `composer analyse` | Análisis estático con Larastan (nivel 8) |
| `composer check` | Las tres revisiones juntas; debe pasar antes de fusionar a `dev` |

## Documentación de funciones

Las clases y funciones se documentan con PHPDoc en el código. phpDocumentor las convierte en un sitio navegable:

```bash
docker compose --profile docs run --rm phpdoc
```

El resultado queda en `docs-build/backend/` (fuera de Git). El CI lo genera en cada PR y falla si algún PHPDoc tiene errores ([ADR-0065](../decisiones/0065-documentacion-de-funciones.md)).

## Dónde está en el código

- [`backend/app/Modules/`](../../backend/app/Modules/)
- [`backend/bootstrap/app.php`](../../backend/bootstrap/app.php) y [`backend/routes/api.php`](../../backend/routes/api.php)
- [`backend/composer.json`](../../backend/composer.json): comandos de calidad.
- [`backend/phpdoc.dist.xml`](../../backend/phpdoc.dist.xml): configuración de phpDocumentor.
