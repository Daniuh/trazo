---
title: "ADR-0059: Sistema en la práctica: salud medida desde la app, eventos agrupados y acciones con reautenticación"
description: "Sistema en la práctica: salud medida desde la app, eventos agrupados y acciones con reautenticación"
audience: tecnico
area: decisiones
order: 69
---

# ADR-0059: Sistema en la práctica: salud medida desde la app, eventos agrupados y acciones con reautenticación

- **Estado:** Aceptada
- **Fecha:** 2026-10-25

## Contexto
El [ADR-0022](0022-logs-relevantes.md) fijó qué se registra (solo lo relevante, agrupado por huella) y el [ADR-0023](0023-acciones-de-sistema.md) qué acciones tiene el Super admin, sin acceso a Docker. Al construirlo había que decidir:

- Cómo saber desde la app si workers, scheduler y Reverb están vivos.
- Qué borra "Limpiar caché".
- Cómo funciona el modo mantenimiento con varios contenedores y una API con token.
- Qué se muestra de un job fallido.
- Cómo se protege cada acción.
- Qué hacer con el ruido que ya había en el log.

## Alternativas evaluadas
Señales de vida:
1. **El scheduler deja la suya cada minuto y encola un job mínimo que deja la del worker.**
2. Revisar los procesos desde fuera (Docker o el sistema operativo).
3. Que el worker escriba su señal en cada vuelta de su ciclo.

Limpiar caché:
1. **Solo la caché de datos (cifras del dashboard).**
2. `cache:clear`: toda la caché de Redis.

Modo mantenimiento:
1. **Una marca propia en Redis y un middleware de la API que deja pasar solo a quien ejecuta acciones de sistema.**
2. `artisan down` de Laravel.

Jobs fallidos:
1. **Solo el nombre del job, la cola, la fecha y la primera línea del error.**
2. El contenido completo, para poder revisarlo.

Registro de eventos:
1. **Una sola sentencia `INSERT … ON DUPLICATE KEY UPDATE` por la huella.**
2. Buscar la fila y después insertar o actualizar.

## Decisión
- **Panel de salud** medido desde la app en cada consulta:
  - Base de datos y Redis: una consulta y un PING, con su tiempo.
  - Cola: pendientes, antigüedad del más viejo y jobs fallidos.
  - Workers y scheduler: su señal de vida.
  - Tiempo real: una conexión al puerto de Reverb.
  - Disco del almacenamiento.

  Los umbrales viven en un solo lugar (`HealthRules`): más de 200 ms, señal de más de 2 minutos, más de 100 pendientes o el más viejo con más de 5 minutos, menos de 10 % o 5 % de disco. El estado general es el peor de todos. Sin señal todavía (recién arrancado) se marca "Atención", no "Falla".
- **Señales de vida:** el scheduler corre `system:check` cada minuto. Deja su propia señal, encola `WorkerHeartbeat` (cuando el worker lo procesa, deja la suya) y registra un evento por cada pieza que falla o por la cola acumulada.
- **Eventos agrupados por huella** (SHA-256 del tipo y su origen) en una sola sentencia. Si se repiten, suman al contador; si estaban resueltos, se reabren. Registrar nunca hace fallar lo que se estaba haciendo: si la base no responde, queda en el log de Laravel. Los resueltos se borran a los 90 días (`SYSTEM_EVENTS_RETENTION_DAYS`).
- **Qué se registra:**

  | Categoría | Eventos |
  |---|---|
  | Fallas | Errores 500 de la API (de un error de base de datos solo queda su código SQLSTATE, nunca la consulta), jobs que agotaron sus intentos y cola acumulada |
  | Infraestructura | Redis, Reverb o el worker sin responder; Have I Been Pwned sin responder |
  | Seguridad | Ingreso bloqueado (con la cuenta enmascarada: `da***@gmail.com`), ingreso cortado por códigos MFA, código de recuperación anulado, reautenticación bloqueada y 10 o más respuestas 403 de la misma persona en 5 minutos |

  Los errores de los jobs se registran una vez, como job fallido, no en cada intento.
- **Sin ruido en el log:** las reglas de negocio que se cumplen (`DomainException`: código incorrecto, conflicto, sin permiso) dejan de reportarse. Antes llegaban como `ERROR` en cada login fallido o cada pedido de reautenticación.
- **Acciones:** reiniciar workers (`queue:restart`), reiniciar Reverb (`reverb:restart`), limpiar la caché de datos, modo mantenimiento, y reintentar o descartar jobs fallidos.
  - Cada una exige `system.actions.execute` y el código de la aplicación de autenticación (la reautenticación de la administración de usuarios). El diálogo del código es también la confirmación.
  - Límite de 5 por minuto por persona. Quedan en `admin_actions` (tipos `system_*`) y se ven en la auditoría.
- **Reiniciar** usa las señales de Laravel: el proceso termina limpio (el worker después del job en curso) y Docker lo levanta otra vez (`restart: unless-stopped`).
- **Limpiar caché** sube la generación de la caché del dashboard. Los bloqueos del login, los límites de frecuencia y las sesiones a medio MFA no se tocan.
- **Mantenimiento:** marca en Redis (`system:maintenance`, con quién y desde cuándo) y el middleware `EnforceMaintenance` en la API.
  - Quien no puede ejecutar acciones de sistema recibe 503 `SERVICE_UNAVAILABLE`, que el front ya muestra como "Trazo está en mantenimiento".
  - Quien puede sigue trabajando y recibe la cabecera `X-Maintenance`, con la que el front muestra un aviso fijo.
  - El ingreso, el segundo paso del MFA y cerrar sesión siguen abiertos, y `/up` (el chequeo de Docker) responde normal.
- **Jobs fallidos sin contenido:** el payload no sale de la API. Solo el nombre del job (de `displayName`), la cola, la fecha y la primera línea del error, recortada a 300 caracteres.
- **Pantalla** con dos pestañas (Salud y Eventos), cada una con su permiso. Las acciones van en la tarjeta a la que corresponden. Los eventos usan el mismo cursor que la auditoría (`TimeCursor`, que pasó a Shared) y se ordenan por la última vez que ocurrieron.

## Alternativas descartadas
- **Revisar procesos desde fuera:** exige acceso a Docker o al servidor, justo lo que el ADR-0023 descarta.
- **Señal en cada vuelta del worker:** escribe en Redis cada pocos segundos y solo prueba que el proceso existe, no que procesa la cola.
- **`cache:clear`:** borraría también los contadores del bloqueo progresivo y de los límites de frecuencia. Limpiar caché se volvería una forma de saltarse un bloqueo.
- **`artisan down`:** guarda la marca en un archivo de cada contenedor (no llega a workers ni a Reverb) y deja pasar a quien tiene una cookie secreta, que no sirve con nuestro token en la cabecera.
- **Mostrar el contenido de un job:** puede traer datos sensibles, como el correo con un código de recuperación.
- **Buscar y después escribir:** dos eventos iguales al mismo tiempo crearían dos filas o perderían una suma.

## Consecuencias
- El Super admin ve en una pantalla si algo falla y lo resuelve sin entrar al servidor. Si cae el scheduler, el panel lo muestra (su señal envejece), aunque no pueda registrar el evento él mismo.
- Mientras la lista se recorre, un evento que se repite cambia de lugar: está ordenada por su última vez. Basta volver a cargarla.
- Un evento se registra en la conexión principal: si se anotara dentro de una transacción que luego se deshace, se perdería. Hoy todos se registran fuera de una transacción.
- Quedan como mejoras propuestas: avisar por correo o en el menú cuando hay un evento crítico, y conectar herramientas externas (Sentry, Grafana).

## Dónde está en el código
- Back:
  - Módulo: [`backend/app/Modules/System/`](../../backend/app/Modules/System/).
  - Registro compartido: [`SystemEvents`](../../backend/app/Modules/Shared/Application/Ports/SystemEvents.php).
  - Errores no reportados y middleware: [`bootstrap/app.php`](../../backend/bootstrap/app.php).
  - Tareas: [`routes/console.php`](../../backend/routes/console.php).
- Front:
  - Módulo: [`frontend/src/modules/system/`](../../frontend/src/modules/system/).
  - Aviso de mantenimiento: [`shared/http/maintenance.ts`](../../frontend/src/shared/http/maintenance.ts).
  - Reautenticación: [`shared/ui/feedback/useReauth.ts`](../../frontend/src/shared/ui/feedback/useReauth.ts).
- Pruebas: [`SystemTest.php`](../../backend/tests/Feature/System/SystemTest.php), [`SystemDomainTest.php`](../../backend/tests/Unit/System/SystemDomainTest.php) y [`e2e/system.spec.ts`](../../frontend/e2e/system.spec.ts).
