---
title: MFA
description: Política de MFA por rol, factores TOTP y códigos de recuperación.
audience: tecnico
area: base-de-datos
module: auth
order: 17
---

# MFA

Tres tablas sostienen la verificación en dos pasos: la política de cada rol, el factor de cada persona y sus códigos de recuperación. Además, `users.mfa_grace_used` cuenta los ingresos en que la persona pospuso configurarlo (ver [users](users.md)).

## mfa_policies

Una fila por rol. Si un rol no tiene fila, no exige MFA.

| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `role_id` | SMALLINT UNSIGNED | PK, FK → `roles.id`, CASCADE | Rol al que aplica |
| `is_required` | BOOLEAN | NOT NULL | Si el rol exige el MFA |
| `grace_logins` | TINYINT UNSIGNED | NOT NULL | Ingresos en que se puede posponer configurarlo. Al instalar: 1 |
| `created_at`, `updated_at` | TIMESTAMP | | |

Con varios roles se aplica la regla más estricta: obligatorio si algún rol lo exige, con la gracia más corta.

## user_mfa_factors

El segundo factor de cada persona. Hoy solo existe TOTP; la columna `method` deja lugar a otros (por ejemplo, passkeys) sin tocar `users`.

| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK | |
| `user_id` | BIGINT UNSIGNED | FK → `users.id`, CASCADE | Dueño del factor; se va con la cuenta |
| `method` | VARCHAR(20) | NOT NULL, CHECK `IN ('totp')` | Tipo de factor |
| `secret` | TEXT | NOT NULL | Secreto TOTP **cifrado** con la clave de la aplicación; nunca en claro |
| `confirmed_at` | TIMESTAMP | NULL | Null mientras la persona no lo confirma con su primer código |
| `last_used_step` | BIGINT UNSIGNED | NULL | Paso de tiempo (30 s) del último código aceptado: uno igual o anterior se rechaza |
| `created_at`, `updated_at` | TIMESTAMP | | |

| Índice | Uso |
|---|---|
| `(user_id, method)` (único) | Un factor por tipo y persona; es también la búsqueda de cada ingreso |

## user_recovery_codes

Los 8 códigos de recuperación de cada persona. Solo se guarda su hash.

| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK | |
| `user_id` | BIGINT UNSIGNED | FK → `users.id`, CASCADE | Dueño de los códigos |
| `code_hash` | CHAR(64) | NOT NULL | SHA-256 del código normalizado (mayúsculas, con guion) |
| `used_at` | TIMESTAMP | NULL | Cuándo se usó; un código usado ya no sirve |
| `created_at` | TIMESTAMP | NOT NULL | Cuándo se generó el juego |

| Índice | Uso |
|---|---|
| `(user_id, code_hash)` (único) | Canjear un código: buscar por persona y hash |

## Ciclo de vida

- **Configurar:** se crea el factor sin confirmar. Si la persona vuelve a empezar, el secreto pendiente se reemplaza.
- **Confirmar:** en una misma transacción se marca `confirmed_at` y se guardan los 8 códigos.
- **Regenerar códigos:** se borran los anteriores y se guardan 8 nuevos, en una transacción.
- **Desactivar:** se borran el factor y sus códigos.
- **Borrar la cuenta:** todo se va con ella (CASCADE).

## Dónde está en el código

- Migraciones: [`000018_create_mfa_policies_table`](../../../backend/database/migrations/2026_11_01_000018_create_mfa_policies_table.php), [`000019_create_user_mfa_factors_table`](../../../backend/database/migrations/2026_11_01_000019_create_user_mfa_factors_table.php), [`000020_create_user_recovery_codes_table`](../../../backend/database/migrations/2026_11_01_000020_create_user_recovery_codes_table.php) y [`000021_add_mfa_grace_used_to_users`](../../../backend/database/migrations/2026_11_01_000021_add_mfa_grace_used_to_users.php)
- Modelo: [`UserMfaFactor`](../../../backend/app/Modules/Auth/Infrastructure/Models/UserMfaFactor.php)
- Acceso: [`EloquentMfaStore`](../../../backend/app/Modules/Auth/Infrastructure/EloquentMfaStore.php) y [`DatabaseMfaPolicies`](../../../backend/app/Modules/Auth/Infrastructure/DatabaseMfaPolicies.php)
- Política inicial: [`MfaPolicySeeder`](../../../backend/database/seeders/MfaPolicySeeder.php)
- Explicación funcional: [Módulo Auth](../../backend/auth.md)
