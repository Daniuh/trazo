---
title: personal_access_tokens
description: Tokens de sesión de los usuarios (Sanctum).
audience: tecnico
area: base-de-datos
module: auth
order: 15
---

# personal_access_tokens

Una fila por sesión abierta. La usa Sanctum para validar el token Bearer de cada petición.

| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK | Va al inicio del token (`12\|trazo_…`) para encontrarlo rápido |
| `tokenable_type` | VARCHAR(20) | NOT NULL, CHECK `= 'user'` | Lo exige Sanctum; fijado a `user` para que la FK no sea ambigua |
| `tokenable_id` | BIGINT UNSIGNED | FK → `users.id`, CASCADE | Dueño de la sesión |
| `name` | VARCHAR(100) | NOT NULL | Tipo de token: `session` o `mfa` (ingreso a medias) |
| `token` | CHAR(64) | UNIQUE, NOT NULL | SHA-256 del token; el valor en claro nunca se guarda |
| `abilities` | TEXT | NULL | `["session"]` en una sesión completa; `["mfa"]` en un ingreso a medias, que solo sirve para el segundo paso |
| `last_used_at` | DATETIME | NULL | Última petición; sirve para cerrar la sesión por inactividad |
| `expires_at` | DATETIME | NULL | Vencimiento máximo: 8 horas en una sesión completa y 5 minutos en un ingreso a medias |
| `created_at`, `updated_at` | TIMESTAMP | | |

## Por qué la FK es real

Sanctum modela al dueño del token con una relación polimórfica (`tokenable_type` + `tokenable_id`). Trazo no admite relaciones polimórficas sin FK:

- Solo los usuarios tienen tokens, así que `tokenable_id` es una FK real a `users`.
- `tokenable_type` está fijado a `user`, con un CHECK en la tabla y un *morph map* obligatorio en la aplicación.

## Índices

| Índice | Uso |
|---|---|
| `token` (único) | Validar el token de cada petición |
| `(tokenable_type, tokenable_id)` | Sesiones de un usuario |
| `tokenable_id` (FK) | Integridad referencial |
| `expires_at` | Limpieza diaria de tokens vencidos |

## Ciclo de vida

- **Se crea** al iniciar sesión.
- **Se borra** al cerrar sesión, cuando la limpieza diaria lo encuentra vencido hace más de un día, o si se borra el usuario (CASCADE).
- **Deja de valer** al vencer, tras una hora sin uso o si la cuenta se desactiva.

## Dónde está en el código

- Migración: [`2026_10_22_000013_create_personal_access_tokens_table.php`](../../../backend/database/migrations/2026_10_22_000013_create_personal_access_tokens_table.php)
- Reglas de la sesión: [Módulo Auth](../../backend/auth.md)
