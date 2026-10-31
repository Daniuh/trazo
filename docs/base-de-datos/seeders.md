---
title: Seeders
description: Datos iniciales, usuarios de demostración e idempotencia.
audience: tecnico
area: base-de-datos
order: 3
---

# Seeders

Los datos iniciales se cargan solos al levantar el stack: el servicio `init` ejecuta las migraciones con `--seed` en cada arranque. Por eso todos los seeders son **idempotentes y no destructivos**:

- Agregan lo que falta, identificándolo por su clave natural (`code` en catálogos, roles y permisos; `email` en usuarios).
- **Nunca pisan lo que ya existe.** Si un administrador ajusta, por ejemplo, el tiempo sugerido de una prioridad o quita un permiso a un rol, volver a sembrar no lo revierte. Los cambios intencionales a los datos base se hacen con una migración.

| Seeder | Qué carga | Entorno |
|---|---|---|
| `IncidentCatalogSeeder` | Prioridades, estados, transiciones y tipos de actividad | Todos |
| `AccessControlSeeder` | Roles, permisos y la matriz inicial | Todos |
| `DemoUserSeeder` | Usuarios de demostración | Todos **menos producción** |

## Usuarios de demostración

| Correo | Rol |
|---|---|
| `superadmin@trazo.test` | Super admin |
| `supervisor@trazo.test` | Supervisor |
| `agente1@trazo.test`, `agente2@trazo.test`, `agente3@trazo.test` | Agente |
| `solicitante1@trazo.test`, `solicitante2@trazo.test`, `solicitante3@trazo.test` | Solicitante |

La contraseña de todos es `Trazo.Demo.2026` (variable `DEMO_USER_PASSWORD`), que cumple la política de 12 o más caracteres. Si un usuario ya existe, el seeder no cambia su contraseña.

Los incidentes de prueba (500 o más, con volumen configurable) se agregan junto con el módulo de incidentes.

## Dónde está en el código

- [`backend/database/seeders/`](../../backend/database/seeders/)
- [`backend/config/trazo.php`](../../backend/config/trazo.php): contraseña de demostración.
- Pruebas: [`backend/tests/Feature/Database/SeederTest.php`](../../backend/tests/Feature/Database/SeederTest.php)
