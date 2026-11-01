---
title: Jobs fallidos
description: Qué pasa cuando un trabajo de la cola falla, cómo se ve sin exponer su contenido y cómo se reintenta o se descarta.
audience: tecnico
area: flujos
module: system
order: 6
---

# Jobs fallidos

Los correos, los avisos de tiempo real y las notificaciones van por la cola (Redis). Si un trabajo falla todas sus veces, queda en `failed_jobs` y se registra un evento del sistema. Desde el panel de salud, el Super admin los ve y decide si reintentarlos o descartarlos.

```flujo
carriles: [Worker, Base de datos, Super admin, API]
pasos:
  - id: falla
    carril: Worker
    tipo: inicio
    texto: Un job agota sus intentos
    va: guarda
  - id: guarda
    carril: Base de datos
    texto: Queda en failed_jobs y deja un evento
    detalle: 'Evento `JobFailed` agrupado por huella en `system_events`. El contenido del job (que puede llevar datos personales) se queda en la base, cifrado si es un correo.'
    va: ve
  - id: ve
    carril: Super admin
    texto: Abre "Jobs fallidos" desde Salud
    detalle: 'Hasta 50 recientes, sin su contenido: nombre, cola, fecha y la primera línea del error. Basta `system.health.view` para verlos.'
    va: decide
  - id: decide
    carril: Super admin
    tipo: decision
    texto: ¿Reintenta o descarta?
    va:
      - { a: reintenta, etiqueta: Reintentar }
      - { a: descarta, etiqueta: Descartar }
  - id: reintenta
    carril: API
    tipo: decision
    texto: Con código, ¿existen los elegidos?
    detalle: 'Uno, varios o todos (`POST /system/failed-jobs/retry`), con las protecciones de las acciones de sistema. Si otra persona ya los atendió, responde 404.'
    si: cola
    no: e404
  - id: e404
    carril: API
    tipo: error
    texto: Responde 404 o 409
  - id: cola
    carril: Worker
    tipo: fin
    texto: Vuelven a la cola y se registra cuántos
    detalle: '`system_jobs_retried` con el número en `admin_actions`.'
  - id: descarta
    carril: API
    tipo: decision
    texto: Con código, ¿existe el job?
    detalle: '`DELETE /system/failed-jobs/{id}`.'
    si: borra
    no: e404b
  - id: e404b
    carril: API
    tipo: error
    texto: Responde 404
  - id: borra
    carril: Base de datos
    tipo: fin
    texto: Lo borra y registra cuál era
    detalle: '`system_job_discarded` con el nombre y la cola del job.'
```

## Qué ve la persona en cada error

| Código | HTTP | Cuándo | Mensaje en pantalla |
|---|---|---|---|
| `FAILED_JOB_NOT_FOUND` | 404 | Otra persona ya lo reintentó o lo descartó | Ese job ya no está en la lista: otra persona lo reintentó o lo descartó. Actualiza la lista. |
| `NO_FAILED_JOBS` | 409 | "Reintentar todos" sin jobs fallidos | No hay jobs fallidos para reintentar. |
| `REAUTHENTICATION_LOCKED` | 423 | Cinco códigos incorrectos en un minuto | Escribiste varios códigos incorrectos seguidos. Espera {wait} y vuelve a intentarlo. |

## Dónde está en el código

- [`ManageFailedJobs`](../../../backend/app/Modules/System/Application/ManageFailedJobs.php): ver, reintentar y descartar.
- [`DatabaseFailedJobs`](../../../backend/app/Modules/System/Infrastructure/DatabaseFailedJobs.php): lectura sin el contenido.
- [`SystemServiceProvider`](../../../backend/app/Modules/System/Infrastructure/SystemServiceProvider.php): el evento al fallar un job.
- Diccionario: [failed_jobs](../../base-de-datos/diccionario/failed-jobs.md).
- Explicación: [Módulo System](../../backend/system.md).
