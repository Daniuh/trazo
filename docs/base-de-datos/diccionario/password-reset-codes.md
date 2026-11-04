---
title: password_reset_codes
description: Solicitudes de recuperación de contraseña con su código, intentos, vencimiento y pase.
audience: tecnico
area: base-de-datos
module: auth
order: 18
---

# password_reset_codes

Una fila por solicitud de recuperación de contraseña. Solo una por persona está vigente: pedir un código nuevo anula las anteriores.

| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK | |
| `user_id` | BIGINT UNSIGNED | FK → `users.id`, CASCADE | Dueño de la solicitud; se va con la cuenta |
| `code_hash` | CHAR(64) | NOT NULL | HMAC-SHA256 del código de 6 dígitos con la clave de la aplicación |
| `attempts` | TINYINT UNSIGNED | NOT NULL, por defecto `0` | Fallos del código o del segundo factor; a los 5 se anula |
| `expires_at` | TIMESTAMP | NOT NULL | El código vale 15 minutos |
| `ticket_hash` | CHAR(64) | NULL | HMAC del pase que entrega el código ya verificado |
| `ticket_expires_at` | TIMESTAMP | NULL | El pase vale 10 minutos |
| `used_at` | TIMESTAMP | NULL | Usada o anulada (por un código nuevo o por intentos): ya no sirve |
| `created_at` | TIMESTAMP | NOT NULL | |

## Por qué HMAC y no un hash simple

El código tiene un millón de combinaciones. Con un SHA-256 simple, quien tuviera una copia de la base lo encontraría en segundos. El HMAC usa la clave de la aplicación, que no está en la base: sin ella no se puede calcular.

## Índices

| Índice | Uso |
|---|---|
| `(user_id, used_at)` | La solicitud vigente de una persona (la más reciente sin usar) |

## Dónde está en el código

- Migración: [`2026_11_01_000022_create_password_reset_codes_table.php`](../../../backend/database/migrations/2026_11_01_000022_create_password_reset_codes_table.php)
- Acceso: [`DatabasePasswordResets`](../../../backend/app/Modules/Auth/Infrastructure/DatabasePasswordResets.php)
- Explicación funcional: [Módulo Auth](../../backend/auth.md)
