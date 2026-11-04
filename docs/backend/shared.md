---
title: Módulo Shared
description: "Piezas transversales de la API: excepciones de dominio, errores y identificador de petición."
audience: tecnico
area: backend
module: shared
order: 2
---

# Módulo Shared

Reúne lo que usan todos los módulos y no pertenece a ningún dominio en particular.

## Excepciones de dominio

Las reglas de negocio no conocen HTTP: lanzan una excepción de dominio con un **código estable** y, si hace falta, los parámetros que necesita el mensaje.

| Excepción | Cuándo usarla | Respuesta |
|---|---|---|
| `ResourceNotFoundException` | El recurso no existe **o** quien consulta no puede verlo | 404 |
| `ConflictException` | Choca con el estado actual (transición inválida, versión desactualizada) | 409 |
| `RuleViolationException` | Los datos son válidos en forma pero rompen una regla de negocio | 422 |
| `AuthenticationFailedException` | No se pudo comprobar quién es la persona | 401 |
| `ForbiddenException` | Puede ver el recurso, pero su rol no incluye esa acción. Por defecto `FORBIDDEN_ACTION`; un módulo puede dar un motivo más preciso (p. ej. `USER_OUTRANKED`) | 403 |
| `TemporarilyLockedException` | Algo quedó bloqueado por un tiempo; indica cuántos segundos faltan | 423, con `Retry-After` |

```php
throw new ConflictException('INVALID_STATUS_TRANSITION', ['from' => 'closed', 'to' => 'open']);
```

Cada módulo define sus propios códigos en su capa de dominio. La traducción a estado HTTP ocurre en un único lugar: el renderizador de errores.

Una excepción de dominio es una respuesta esperada (un código incorrecto, un conflicto, falta de permiso), no una falla: `bootstrap/app.php` la excluye del reporte de Laravel (`dontReport`), así no llena el log. Lo que sí es una falla (un 500) queda además como [evento del sistema](system.md#eventos).

## Errores de la API

Toda excepción que llega a la API, sea de negocio, de validación, del framework o inesperada, pasa por `ApiExceptionRenderer` y sale en formato RFC 9457 sin detalles técnicos. El formato completo y la lista de códigos están en [Errores de la API](../api/errores.md).

## Identificador de petición

Cada petición recibe un identificador que:

- se devuelve en la cabecera `X-Request-Id`,
- aparece como `trace_id` en los errores,
- se agrega al contexto de todos los logs de esa petición.

Así, cuando alguien reporta un problema con su identificador, se encuentra en los logs sin exponer nada técnico. Si el cliente envía su propio `X-Request-Id` se reutiliza, pero solo si tiene un formato seguro (letras, números y guiones, de 8 a 64 caracteres); si no, se genera un ULID nuevo.

## JSON en toda la API

Las peticiones a `/api/*` se tratan siempre como JSON, aunque el cliente no envíe la cabecera `Accept`. Por eso incluso una ruta inexistente responde con un error JSON y no con una página HTML.

## Normalización de correos

`EmailAddress::normalize()` es la única regla para limpiar un correo: quita los espacios y lo pasa a minúsculas. La usan el modelo de usuario al guardar y el login al buscar, así ambos aplican siempre lo mismo.

## Límite general de peticiones

Toda la API tiene un límite de 120 peticiones por minuto: por usuario si hay sesión, y por IP si no la hay. Lo define `AppServiceProvider` (`trazo.api_rate_limit`). En producción se queda en 120; la variable `API_RATE_LIMIT` solo se sube a mano para un escaneo o una prueba de carga en local. Las rutas sensibles, como el login, suman su propio límite, más estricto.

**Dónde viven los contadores.** Los límites de frecuencia (`RateLimiter`) y los bloqueos del login usan su propio almacén (`cache.limiter = limits`), en la base 2 de Redis (`REDIS_LIMITS_DB`), aparte de la caché de datos (base 1). Limpiar la caché de datos nunca levanta un bloqueo.

En desarrollo, `php artisan trazo:reset-limits` vacía solo esa base: la suite E2E lo usa al empezar para poder repetirse sin esperar 15 minutos. En producción el comando no se registra.

## Nombres de migración

Los archivos de migración llevan la fecha `2026_11_01` y un número de seis cifras que fija el orden. Una base creada cuando los archivos tenían su fecha de escritura (`2026_10_DD`) se pone al día sola: antes de `migrate`, `migrate:status` o `migrate:rollback`, `MigrationNames` cambia ese prefijo en la tabla `migrations`. No toca datos y en una base nueva no hace nada ([ADR-0091](../decisiones/0091-nombres-de-migracion-con-una-sola-fecha.md)).

## Carga diferida estricta

Fuera de producción, `AppServiceProvider` activa `Model::preventLazyLoading()`: si un código lee una relación que no se pidió con `with()`, Eloquent lanza una excepción en lugar de hacer una consulta más por cada registro. Así un N+1 nuevo falla en desarrollo y en la suite, aunque nadie haya escrito una prueba que cuente consultas para esa pantalla. En producción queda apagado, para que un caso no previsto se vuelva lento y no se caiga ([ADR-0061](../decisiones/0061-entrega-verificada-desde-cero.md)).

## Puertos que comparten los módulos

| Pieza | Capa | Para qué |
|---|---|---|
| `Clock` | Application | La hora actual, inyectada para que las reglas de fechas se puedan probar |
| `Transaction` | Application | Varias escrituras como una sola: o se guardan todas o ninguna |
| `RequestContext` | Application | Desde dónde se hizo un cambio (IP y navegador), para los registros de auditoría |
| `LaravelSupport` | Infrastructure | Implementa `Clock` y `Transaction` sobre Laravel; se conecta en `AppServiceProvider` |
| `AdminLog`, `AdminActionType` | Application | Registro de acciones administrativas en `admin_actions`, con su tipo del catálogo |
| `DatabaseAdminLog` | Infrastructure | Implementa `AdminLog`; lee los tipos una vez por petición |
| `SessionSignals` | Application | Avisa en vivo que la sesión de alguien cambió ([Realtime](realtime.md)) |
| `SystemEvents`, `SystemEventType` | Application | Registro de eventos del sistema agrupados por huella; lo implementa el [módulo System](system.md) |

`Clock`, `Transaction` y `RequestContext` vivían en el módulo de incidentes; al necesitarlos también Usuarios y el Dashboard, se movieron aquí para que ningún módulo dependa de otro por algo tan general. `AdminLog` vivía en Usuarios y se movió cuando la exportación de incidentes también tuvo que dejar constancia ([ADR-0058](../decisiones/0058-exportacion-en-la-practica.md)); la fase de sistema registrará ahí sus acciones.

## Cursor de listas

`TimeCursor` (Domain) es el "cargar más" de las listas que crecen sin parar: la fecha y el id del último registro entregado, en base64 sin relleno. La siguiente tanda empieza justo después, sin importar cuántos registros lleguen mientras tanto. La regla `Http/Rules/Cursor` rechaza uno alterado a mano (`INVALID_CURSOR`). Lo usan la [auditoría](audit.md) y los [eventos del sistema](system.md#eventos).

## Descarga en CSV

`CsvDownload` (capa Http) arma una descarga en CSV que se escribe por partes, sin juntar las filas en memoria:

- UTF-8 con BOM y punto y coma como separador, para que el Excel en español la abra bien.
- Celdas entre comillas solo cuando hace falta, y líneas terminadas en `\r\n`.
- `CsvDownload::cell` neutraliza la inyección de fórmulas: si una celda empieza por `=`, `+`, `-`, `@`, tabulador o salto de línea, le antepone un apóstrofo.
- Sin caché (`Cache-Control: no-store`).

La usa la [exportación de incidentes](incidents.md#exportación); cada módulo decide sus columnas y su formato.

## Dónde está en el código

- [`Domain/Exceptions/`](../../backend/app/Modules/Shared/Domain/Exceptions/): `DomainException` y sus tipos.
- [`Http/Errors/ApiExceptionRenderer.php`](../../backend/app/Modules/Shared/Http/Errors/ApiExceptionRenderer.php): conversión de excepciones a errores.
- [`Http/Errors/ValidationErrorFormatter.php`](../../backend/app/Modules/Shared/Http/Errors/ValidationErrorFormatter.php): reglas de validación a códigos.
- [`Http/Middleware/AssignRequestId.php`](../../backend/app/Modules/Shared/Http/Middleware/AssignRequestId.php) y [`ForceJsonResponse.php`](../../backend/app/Modules/Shared/Http/Middleware/ForceJsonResponse.php).
- [`Domain/EmailAddress.php`](../../backend/app/Modules/Shared/Domain/EmailAddress.php) y [`app/Providers/AppServiceProvider.php`](../../backend/app/Providers/AppServiceProvider.php).
- [`Application/Ports/`](../../backend/app/Modules/Shared/Application/Ports/), [`Application/Data/RequestContext.php`](../../backend/app/Modules/Shared/Application/Data/RequestContext.php), [`Application/AdminActionType.php`](../../backend/app/Modules/Shared/Application/AdminActionType.php), [`Infrastructure/LaravelSupport.php`](../../backend/app/Modules/Shared/Infrastructure/LaravelSupport.php) y [`Infrastructure/DatabaseAdminLog.php`](../../backend/app/Modules/Shared/Infrastructure/DatabaseAdminLog.php).
- [`Http/CsvDownload.php`](../../backend/app/Modules/Shared/Http/CsvDownload.php): descarga en CSV.
- [`Domain/TimeCursor.php`](../../backend/app/Modules/Shared/Domain/TimeCursor.php) y [`Http/Rules/Cursor.php`](../../backend/app/Modules/Shared/Http/Rules/Cursor.php): cursor de listas.
- [`Application/Ports/SystemEvents.php`](../../backend/app/Modules/Shared/Application/Ports/SystemEvents.php) y [`Application/SystemEventType.php`](../../backend/app/Modules/Shared/Application/SystemEventType.php): registro de eventos del sistema.
- [`Infrastructure/Database/MigrationNames.php`](../../backend/app/Modules/Shared/Infrastructure/Database/MigrationNames.php): nombres de migración al día.
- Pruebas: [`tests/Feature/Shared/`](../../backend/tests/Feature/Shared/) y [`tests/Feature/Database/MigrationNamesTest.php`](../../backend/tests/Feature/Database/MigrationNamesTest.php).
