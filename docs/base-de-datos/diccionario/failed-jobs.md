---
title: failed_jobs
description: Trabajos de la cola que fallaron después de agotar sus intentos, para revisarlos, reintentarlos o descartarlos.
audience: tecnico
area: base-de-datos
module: system
order: 21
---

# failed_jobs

Una fila por trabajo de la cola que falló tres veces seguidas (`--tries=3` del worker): un correo que no salió, un aviso de tiempo real que no se pudo enviar. Es la tabla estándar de Laravel, y la única de infraestructura que vive en MySQL: las colas y la caché están en Redis.

| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK | |
| `uuid` | VARCHAR(255) | NOT NULL, único | Identificador del trabajo; con él se reintenta o se descarta |
| `connection` | TEXT | NOT NULL | Conexión de cola (`redis`) |
| `queue` | TEXT | NOT NULL | Nombre de la cola (`default`) |
| `payload` | LONGTEXT | NOT NULL | El trabajo serializado tal como estaba en la cola |
| `exception` | LONGTEXT | NOT NULL | Error completo con su traza |
| `failed_at` | TIMESTAMP | NOT NULL, por defecto la hora actual | Cuándo falló el último intento |

## Sin claves foráneas

No referencia a ninguna otra tabla: guarda el trabajo como texto, y es el driver `database-uuids` de Laravel el que la escribe. Los avisos de tiempo real viajan sin datos (solo el identificador del incidente). Los correos necesitan el nombre y la dirección de la persona y, en la recuperación de contraseña, el código de un solo uso. Por eso **todo correo en cola viaja cifrado** (`ShouldBeEncrypted`) con la clave de la aplicación: en Redis y en esta tabla el `payload` es ilegible sin `APP_KEY` ([ADR-0062](../../decisiones/0062-correos-cifrados-en-la-cola.md)). Una prueba de arquitectura falla si un correo nuevo no lo implementa.

## Qué se muestra y qué no

El panel de sistema lista solo el nombre del trabajo, la cola, la fecha y la primera línea del error (máximo 300 caracteres). El `payload` nunca sale de la base ni llega a la API. Reintentar y descartar usan los comandos de Laravel (`queue:retry` y `queue:forget`), con reautenticación y registro en `admin_actions`.

## Índices

| Índice | Uso |
|---|---|
| `uuid` (único) | Reintentar o descartar un trabajo puntual |

La lista ordena por `failed_at` sin índice propio. Es intencional: la tabla solo crece cuando algo falla, y el Super admin la vacía al reintentar o descartar. Cada falla genera además un evento del sistema (`job_failed`), que es lo que se vigila.

## Dónde está en el código

- Migración: [`2026_11_01_000012_create_failed_jobs_table.php`](../../../backend/database/migrations/2026_11_01_000012_create_failed_jobs_table.php)
- Acceso: [`DatabaseFailedJobs`](../../../backend/app/Modules/System/Infrastructure/DatabaseFailedJobs.php)
- Configuración: [`config/queue.php`](../../../backend/config/queue.php) (`failed`)
- Explicación funcional: [Módulo System](../../backend/system.md)
