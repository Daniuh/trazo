---
title: Registro de eventos del sistema
description: Qué fallas y señales de seguridad se registran, cómo se agrupan por huella con un contador y cómo se revisan y resuelven.
audience: tecnico
area: flujos
module: system
order: 2
---

# Registro de eventos del sistema

Los "logs del sistema" de Trazo no son un volcado de texto: son eventos relevantes, agrupados. Una misma falla cae siempre en la misma fila (por su huella) y suma a su contador, en lugar de llenar la pantalla de repetidos. Solo el Super admin los ve (`system.logs.view`).

Qué se registra: errores 500, jobs que fallan, piezas caídas (Redis, Reverb, worker) y cola acumulada, ingresos bloqueados, MFA cortado por intentos, códigos de recuperación de contraseña anulados, Have I Been Pwned sin responder, reautenticación bloqueada y 10 accesos denegados en 5 minutos. No se registran tokens vencidos, errores de validación, 404 normales ni intentos de ingreso aislados.

```flujo
carriles: [Origen, Base de datos, Super admin]
pasos:
  - id: pasa
    carril: Origen
    tipo: inicio
    texto: Ocurre algo relevante
    detalle: 'Desde cualquier módulo, por el puerto compartido `SystemEvents`, con el tipo y su origen (por ejemplo `redis`, o la cuenta enmascarada `a***@dominio`).'
    va: huella
  - id: huella
    carril: Origen
    texto: Calcula la huella del tipo y el origen
    detalle: 'SHA-256 de "tipo|origen". El contexto no lleva secretos; de un error de base de datos solo se guarda el SQLSTATE.'
    va: upsert
  - id: upsert
    carril: Base de datos
    texto: Inserta o suma en una sola sentencia
    detalle: '`INSERT … ON DUPLICATE KEY UPDATE`: si la huella ya existe, sube `occurrences`, actualiza la última vez y el mensaje, y si estaba resuelto lo reabre. Nunca lanza: registrar un evento no puede tumbar la petición que lo originó.'
    va: lista
  - id: lista
    carril: Super admin
    texto: Abre Sistema › Eventos
    detalle: 'Vista Pendientes, Resueltos o Todos, con severidad (info, warning, critical) y tipo; filtros en la dirección.'
    va: detalle
  - id: detalle
    carril: Super admin
    texto: Abre un evento
    detalle: Mensaje, contexto, ocurrencias, primera y última vez y `trace_id` para buscarlo en los registros del servidor.
    va: estado
  - id: estado
    carril: Super admin
    tipo: decision
    texto: ¿Qué hace con él?
    va:
      - { a: revisado, etiqueta: Revisado }
      - { a: resuelto, etiqueta: Resuelto }
  - id: revisado
    carril: Base de datos
    tipo: fin
    texto: Queda revisado, con quién y cuándo
    detalle: 'Sigue sumando si vuelve a pasar.'
  - id: resuelto
    carril: Base de datos
    tipo: fin
    texto: Queda resuelto hasta que se repita
    detalle: 'Si vuelve a ocurrir, se reabre solo. Los resueltos de más de 90 días se borran cada día (`SYSTEM_EVENTS_RETENTION_DAYS`).'
```

## Qué ve la persona en cada error

| Código | HTTP | Cuándo | Mensaje en pantalla |
|---|---|---|---|
| `SYSTEM_EVENT_NOT_FOUND` | 404 | El evento se borró por antigüedad | Ese evento ya no existe: se borró por antigüedad. Actualiza la lista. |
| `FORBIDDEN_ACTION` | 403 | Sin `system.logs.view` | No tienes permiso para hacer esta acción. Si crees que deberías tenerlo, habla con un administrador. |

## Dónde está en el código

- [`DatabaseSystemEvents`](../../../backend/app/Modules/System/Infrastructure/DatabaseSystemEvents.php) y [`Fingerprint`](../../../backend/app/Modules/System/Domain/Fingerprint.php): registro agrupado.
- [`FailureEvents`](../../../backend/app/Modules/System/Infrastructure/FailureEvents.php) y [`bootstrap/app.php`](../../../backend/bootstrap/app.php): errores 500.
- [`WatchDeniedAccess`](../../../backend/app/Modules/System/Http/Middleware/WatchDeniedAccess.php): accesos denegados repetidos.
- [`ManageSystemEvents`](../../../backend/app/Modules/System/Application/ManageSystemEvents.php): listar y cambiar el estado.
- [`useSystemEvents`](../../../frontend/src/modules/system/application/useSystemEvents.ts).
- Explicación: [Módulo System](../../backend/system.md).
