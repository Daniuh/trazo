---
title: Panel de salud
description: Cómo se mide cada pieza de Trazo en el momento, cómo se interpreta con umbrales fijos y cómo la revisión de cada minuto deja eventos cuando algo falla.
audience: tecnico
area: flujos
module: system
order: 3
---

# Panel de salud

"Sistema › Salud" muestra una tarjeta por pieza (base de datos, Redis, cola y worker, scheduler, tiempo real, almacenamiento) en verde, atención o falla. Se mide en el momento de abrirlo, no se guarda. Aparte, el scheduler revisa lo mismo cada minuto y deja un evento cuando algo falla, aunque nadie tenga el panel abierto.

| Medida | Atención | Falla |
|---|---|---|
| Base de datos y Redis | Más de 200 ms | No responde |
| Worker y scheduler | Sin señal todavía (recién arrancado) | Última señal hace más de 2 min |
| Cola | Más de 100 pendientes, el más viejo con más de 5 min, o jobs fallidos | — |
| Almacenamiento | Menos del 10 % libre | Menos del 5 % libre |

```flujo
carriles: [Super admin, API, Piezas, Scheduler]
pasos:
  - id: abre
    carril: Super admin
    tipo: inicio
    texto: Abre Sistema › Salud
    va: permiso
  - id: permiso
    carril: API
    tipo: decision
    texto: ¿Tiene system.health.view?
    si: mide
    no: e403
  - id: e403
    carril: API
    tipo: error
    texto: Responde 403
  - id: mide
    carril: Piezas
    texto: Mide cada pieza ahora
    detalle: 'Una consulta a MySQL, un ping a Redis, la conexión a Reverb, la cola y los jobs fallidos, el espacio en disco y las señales de vida del worker y del scheduler.'
    va: interpreta
  - id: interpreta
    carril: API
    texto: Interpreta con HealthRules
    detalle: Los mismos umbrales que usa la revisión de cada minuto, definidos en un solo lugar.
    va: muestra
  - id: muestra
    carril: Super admin
    tipo: fin
    texto: Ve cada tarjeta con su estado y sus acciones
    detalle: 'Si tiene `system.actions.execute`, cada tarjeta trae las acciones de esa pieza (ver "Acciones de sistema").'
  - id: minuto
    carril: Scheduler
    tipo: inicio
    texto: Corre system:check cada minuto
    va: senales
  - id: senales
    carril: Scheduler
    texto: Deja su señal de vida y pide la del worker
    detalle: 'El scheduler escribe la suya en Redis y encola `WorkerHeartbeat`: si el worker está vivo, lo procesa y deja la suya.'
    va: falla
  - id: falla
    carril: Piezas
    tipo: decision
    texto: ¿Algo falla o la cola se acumula?
    si: evento
    no: nada
  - id: nada
    carril: Scheduler
    tipo: fin
    texto: Nada que registrar
  - id: evento
    carril: Scheduler
    tipo: fin
    texto: Registra el evento de esa pieza
    detalle: '`ServiceDown` (redis, realtime o worker) o `QueueBacklog`, agrupados por huella: un minuto tras otro suman al mismo evento.'
```

## Dónde está en el código

- [`ReadHealth`](../../../backend/app/Modules/System/Application/ReadHealth.php) y [`HealthRules`](../../../backend/app/Modules/System/Domain/HealthRules.php).
- [`LaravelHealthProbe`](../../../backend/app/Modules/System/Infrastructure/LaravelHealthProbe.php): las mediciones.
- [`CheckSystem`](../../../backend/app/Modules/System/Application/CheckSystem.php) y [`CheckSystemCommand`](../../../backend/app/Modules/System/Infrastructure/Console/CheckSystemCommand.php): la revisión de cada minuto.
- [`CacheHeartbeats`](../../../backend/app/Modules/System/Infrastructure/CacheHeartbeats.php): señales de vida.
- [`useHealth`](../../../frontend/src/modules/system/application/useHealth.ts).
- Explicación: [Módulo System](../../backend/system.md).
