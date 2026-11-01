---
title: Módulo System
description: Panel de salud, eventos del sistema agrupados por huella y acciones del Super admin (reiniciar, limpiar caché, mantenimiento y jobs fallidos), sin acceso a Docker.
audience: tecnico
area: backend
module: system
order: 9
---

# Módulo System

Le da al Super admin una pantalla para ver si algo falla y resolverlo sin entrar al servidor. Mide la salud de cada pieza, registra los eventos relevantes agrupados y ejecuta acciones dentro del alcance de la app: nunca toca Docker ([ADR-0022](../decisiones/0022-logs-relevantes.md), [ADR-0023](../decisiones/0023-acciones-de-sistema.md) y [ADR-0059](../decisiones/0059-sistema-en-la-practica.md)).

## Endpoints

| Método | Ruta | Para qué | Permiso |
|---|---|---|---|
| `GET` | `/system/health` | Panel de salud | `system.health.view` |
| `GET` | `/system/events` | Eventos por cursor, con filtros | `system.logs.view` |
| `GET` | `/system/events/types` | Catálogo de tipos, para el filtro | `system.logs.view` |
| `PATCH` | `/system/events/{id}` | Cambiar el estado (`status`) | `system.logs.view` |
| `GET` | `/system/failed-jobs` | Los 50 jobs fallidos más recientes y el total | `system.health.view` |
| `POST` | `/system/failed-jobs/retry` | Reintentar `ids` o `all: true` | Acción |
| `DELETE` | `/system/failed-jobs/{uuid}` | Descartar uno | Acción |
| `POST` | `/system/actions/{action}` | `restart-workers`, `restart-realtime` o `clear-cache` | Acción |
| `PUT` | `/system/maintenance` | Activar o desactivar (`enabled`) | Acción |
| `GET` | `/docs` | Contrato OpenAPI en JSON ([API](../api/index.md#referencia-openapi)) | `system.health.view` |

Todas van bajo `/api/v1` y exigen sesión. Los tres permisos son solo del Super admin, salvo que se concedan a una persona. "Acción" quiere decir:

- Permiso `system.actions.execute`, revisado antes de validar.
- Reautenticación: el código de la aplicación de autenticación en `mfa_code`. Sin él responde 422 `REAUTHENTICATION_REQUIRED`, y el front abre el diálogo del código.
- 5 acciones por minuto por persona (`throttle:system-actions`).
- Registro en `admin_actions` con un tipo `system_*`, visible en [Auditoría](audit.md).

## Salud

`GET /system/health` mide en el momento (`LaravelHealthProbe`) y lo interpreta con `HealthRules`:

| Componente | Qué mide | Atención | Falla |
|---|---|---|---|
| `database` | Una consulta a MySQL | Más de 200 ms | No responde |
| `redis` | Un PING | Más de 200 ms | No responde |
| `queue` | Pendientes, antigüedad del más viejo y fallidos | Más de 100 pendientes, el más viejo con más de 5 min, o algún fallido | — |
| `worker` | Su señal de vida | Sin señal todavía (recién arrancado) | Señal de más de 2 min |
| `scheduler` | Su señal de vida | Sin señal todavía | Señal de más de 2 min |
| `realtime` | Conexión al puerto de Reverb | — | No acepta conexiones |
| `storage` | Espacio libre del disco | Menos de 10 % | Menos de 5 % |

La respuesta trae el estado general (el peor de todos), cada componente con sus datos, el modo mantenimiento (desde cuándo y quién lo activó) y la app (entorno, versión de Laravel y de PHP).

**Señales de vida.** El scheduler corre `system:check` cada minuto: deja su señal en Redis y encola `WorkerHeartbeat`, que deja la del worker cuando este lo procesa. Así se comprueba que la cola de verdad se procesa, no solo que el proceso existe.

## Eventos

`SystemEvents` (puerto compartido en Shared) es la única forma de registrar. Lo usan:

| Tipo | Categoría | Severidad | Quién lo registra |
|---|---|---|---|
| `unhandled_error` | Falla | Crítica | El manejo de errores, con cada 500 de la API |
| `job_failed` | Falla | Atención | El oyente de `JobFailed` (una vez, al agotar los intentos) |
| `queue_backlog` | Falla | Atención | `system:check` |
| `service_down` | Infraestructura | Crítica | `system:check` (Redis, Reverb o worker) |
| `external_service_unavailable` | Infraestructura | Atención | La consulta de contraseñas filtradas |
| `login_locked` | Seguridad | Atención | El login, al bloquear una cuenta |
| `mfa_attempts_exceeded` | Seguridad | Atención | El segundo paso, al cortar un ingreso |
| `reset_code_voided` | Seguridad | Atención | La recuperación, al anular un código |
| `reauthentication_locked` | Seguridad | Crítica | La reautenticación, al quinto código incorrecto |
| `access_denied_repeated` | Seguridad | Atención | `WatchDeniedAccess`, con 10 respuestas 403 de la misma persona en 5 min |

- **Agrupación.** La huella es SHA-256 del tipo y su origen (la clase y la línea de un error, un servicio, una cuenta). `DatabaseSystemEvents` hace un `INSERT … ON DUPLICATE KEY UPDATE`: suma al contador, actualiza mensaje, contexto, última vez y código de soporte, y reabre lo resuelto.
- **Sin datos sensibles.** De un error de base de datos queda solo su código SQLSTATE. La cuenta de un login bloqueado va enmascarada (`EmailAddress::masked`). Nunca se guardan tokens, contraseñas ni contenido de jobs.
- **Nunca rompe nada.** Si registrar falla, queda en el log de Laravel y la petición sigue.
- **Estados:** abierto → revisado → resuelto; un resuelto se puede reabrir. Al cambiarlo queda quién y cuándo. Pedir el mismo estado no cambia nada.
- **Lista:** de la última vez más reciente a la más antigua, con filtros por estado, severidad y tipo (separados por comas) y el mismo cursor de la auditoría (`TimeCursor`).
- **Limpieza:** `system:prune-events` borra cada día los resueltos hace más de 90 días (`trazo.system.events_retention_days`).

**Lo que no se registra** (es una respuesta esperada, no una falla): las reglas de negocio (`DomainException`), los errores de validación, los 404 y los tokens vencidos. `bootstrap/app.php` las excluye del reporte de Laravel.

## Acciones

| Acción | Qué hace | Tipo en `admin_actions` |
|---|---|---|
| Reiniciar workers | `queue:restart`: cada worker termina el job en curso y Docker lo levanta | `system_workers_restarted` |
| Reiniciar el tiempo real | Deja en la caché la misma señal que `reverb:restart` (`laravel:reverb:restart`): Reverb la revisa, se cierra y Docker lo levanta; las pantallas se reconectan solas. Se escribe directo porque Reverb solo registra sus comandos en la consola, no en una petición web | `system_realtime_restarted` |
| Limpiar la caché de datos | Sube la generación de la caché del dashboard. Bloqueos, límites y sesiones no se tocan | `system_cache_cleared` |
| Activar o desactivar mantenimiento | Marca en Redis con quién y desde cuándo | `system_maintenance_enabled` / `_disabled` |
| Reintentar jobs | `queue:retry` de los elegidos o de todos | `system_jobs_retried` (`count`) |
| Descartar un job | `queue:forget` | `system_job_discarded` (`job`, `queue`) |

## Modo mantenimiento

`EnforceMaintenance` corre en todas las rutas de la API:

- Sin mantenimiento, no hace nada.
- Con mantenimiento, quien no tiene `system.actions.execute` recibe 503 `SERVICE_UNAVAILABLE`. Quien lo tiene sigue trabajando y cada respuesta lleva `X-Maintenance: on`.
- Siempre abiertos: `auth.login`, `auth.mfa.challenge.*` y `auth.logout`, para que el Super admin pueda entrar. `/up` no pasa por la API y responde normal.
- Si Redis no responde, se considera apagado: no conviene cerrar la app por una falla que ya es mayor.

## Errores

| Código | Estado | Cuándo |
|---|---|---|
| `REAUTHENTICATION_REQUIRED`, `MFA_CODE_INVALID`, `REAUTHENTICATION_LOCKED`, `REAUTHENTICATION_UNAVAILABLE` | 422 / 423 | Ver [Users](users.md) |
| `MAINTENANCE_ALREADY_ENABLED`, `MAINTENANCE_ALREADY_DISABLED` | 409 | Pedir el estado que ya tiene |
| `NO_FAILED_JOBS` | 409 | Reintentar todos sin ninguno |
| `FAILED_JOB_NOT_FOUND` | 404 | Otra persona ya lo reintentó o lo descartó |
| `SYSTEM_EVENT_NOT_FOUND` | 404 | Se borró por antigüedad |
| `INVALID_CURSOR` | 422 | Cursor alterado a mano |
| `SERVICE_UNAVAILABLE` | 503 | Modo mantenimiento |

## Capas

| Capa | Piezas |
|---|---|
| Domain | `Operator` (permisos), `HealthRules`, `HealthStatus`, `EventStatus`, `SystemAction`, `Fingerprint`, `Exceptions/SystemExceptions` |
| Application | `ReadHealth`, `ManageSystemEvents`, `ManageFailedJobs`, `RunSystemAction`, `SetMaintenance`, `CheckSystem`, `SystemGuard` (permiso y reautenticación); datos (`HealthReport`, `HealthMeasurements`, `ComponentHealth`, `MaintenanceState`, `EventRecord`, `EventPage`, `EventCriteria`, `EventType`, `FailedJob`, `FailedJobList`); puertos `HealthProbe`, `Heartbeats`, `MaintenanceSwitch`, `SystemControls`, `FailedJobs`, `SystemEventLog`, `IdentityCheck` |
| Infrastructure | `DatabaseSystemEvents` (implementa el puerto compartido), `DatabaseEventLog`, `LaravelHealthProbe`, `CacheHeartbeats`, `WorkerHeartbeat`, `CacheMaintenanceSwitch`, `LaravelSystemControls`, `DatabaseFailedJobs`, `UsersIdentityCheck`, `FailureEvents`, `Console/CheckSystemCommand`, `Console/PruneSystemEventsCommand`, `SystemServiceProvider` |
| Http | `SystemController`, `ApiDocsController` (contrato OpenAPI), `SystemResource`, `RequestOperator`, `Requests/*`, `Middleware/EnforceMaintenance`, `Middleware/WatchDeniedAccess` |

## Dónde está en el código

- [`backend/app/Modules/System/`](../../backend/app/Modules/System/)
- Registro compartido: [`Shared/Application/Ports/SystemEvents.php`](../../backend/app/Modules/Shared/Application/Ports/SystemEvents.php) y [`Shared/Application/SystemEventType.php`](../../backend/app/Modules/Shared/Application/SystemEventType.php)
- Manejo de errores y middleware: [`bootstrap/app.php`](../../backend/bootstrap/app.php); tareas: [`routes/console.php`](../../backend/routes/console.php)
- Pruebas: [`tests/Feature/System/SystemTest.php`](../../backend/tests/Feature/System/SystemTest.php) y [`tests/Unit/System/SystemDomainTest.php`](../../backend/tests/Unit/System/SystemDomainTest.php)
- Tablas: [system_events](../base-de-datos/diccionario/system-events.md) y [admin_actions](../base-de-datos/diccionario/admin-actions.md)
- Operación: [Sistema](../operacion/sistema.md)
