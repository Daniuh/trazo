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
│   ├── Auth/              → sesión: login, logout, bloqueo progresivo
│   ├── Dashboard/         → widgets, métricas en caché y diseño por persona
│   ├── Incidents/         → ciclo de vida de los incidentes y catálogos
│   ├── Users/             → usuarios, roles y permisos
│   └── Shared/            → lo transversal: errores, identificador de petición
│       ├── Domain/
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
| Auth | Login, logout y sesión listos; MFA y recuperación pendientes | [Auth](auth.md) |
| Incidents | CRUD, estados, asignación, historial y listado listos | [Incidents](incidents.md) |
| Dashboard | Widgets, métricas y diseño por persona listos | [Dashboard](dashboard.md) |
| Users | Modelos y permisos efectivos (`UserPermissions`); administración pendiente | [Modelo de datos](../base-de-datos/modelo.md) |
| System | Pendiente | — |

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

## Dónde está en el código

- [`backend/app/Modules/`](../../backend/app/Modules/)
- [`backend/bootstrap/app.php`](../../backend/bootstrap/app.php) y [`backend/routes/api.php`](../../backend/routes/api.php)
- [`backend/composer.json`](../../backend/composer.json): comandos de calidad.
