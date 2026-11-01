---
title: Vencidos cada 5 minutos
description: La tarea programada que avisa una sola vez de cada incidente que acaba de vencer sin resolverse.
audience: tecnico
area: flujos
module: notifications
order: 6
---

# Vencidos cada 5 minutos

"Vencido" no se guarda: un incidente está vencido cuando su vencimiento ya pasó y sigue pendiente (Abierto, En progreso o En espera). Como nadie hace un cambio en ese momento, ningún evento lo anuncia: una tarea programada lo busca cada 5 minutos.

```flujo
carriles: [Scheduler, Base de datos, Cola, Reverb, Destinatarios]
pasos:
  - id: tarea
    carril: Scheduler
    tipo: inicio
    texto: Corre notifications:overdue
    detalle: Cada 5 minutos y sin solaparse con la corrida anterior (`withoutOverlapping`).
    va: busca
  - id: busca
    carril: Base de datos
    texto: Busca los que vencieron en las últimas 24 h sin aviso
    detalle: 'Pendientes cuyo vencimiento cayó en el último día y que no tienen un aviso de vencido creado después de vencer. Así cada vencimiento avisa una sola vez sin columnas nuevas, y si la tarea estuvo detenida más de un día no llega una avalancha de avisos viejos.'
    va: hay
  - id: hay
    carril: Scheduler
    tipo: decision
    texto: ¿Hay alguno?
    si: coordinan
    no: fin
  - id: fin
    carril: Scheduler
    tipo: fin
    texto: Termina hasta la siguiente corrida
  - id: coordinan
    carril: Base de datos
    texto: Busca a quienes pueden asignar
    va: guarda
  - id: guarda
    carril: Base de datos
    texto: Guarda "vencido" para el asignado y quienes asignan
    detalle: Un aviso por persona e incidente, en la pestaña Actividad.
    va: senal
  - id: senal
    carril: Reverb
    texto: Envía notifications.changed
    detalle: Una sola vez por persona, aunque reciba varios avisos en la misma corrida.
    va: ven
  - id: ven
    carril: Destinatarios
    tipo: fin
    texto: La campana muestra los vencidos
```

Si un vencido se reprograma con una fecha nueva y vuelve a vencer, vuelve a avisar: el aviso anterior fue creado antes del nuevo vencimiento.

## Dónde está en el código

- [`NotifyOverdue`](../../../backend/app/Modules/Notifications/Application/NotifyOverdue.php): ventana de 24 h y destinatarios.
- [`DatabaseOverdueIncidents`](../../../backend/app/Modules/Notifications/Infrastructure/DatabaseOverdueIncidents.php): la búsqueda sin aviso previo.
- [`routes/console.php`](../../../backend/routes/console.php): la programación cada 5 minutos.
- Explicación: [Módulo Notifications](../../backend/notifications.md).
