---
title: Notificaciones a participantes
description: A quién avisa Trazo de cada mensaje y cada cambio de un incidente, cómo se guardan los avisos y cómo se leen en la campana.
audience: tecnico
area: flujos
module: notifications
order: 4
---

# Notificaciones a participantes

Las notificaciones viven en la campana del dock, en dos pestañas: Conversaciones (mensajes) y Actividad (cambios). Se crean en la cola, para que nunca demoren ni hagan fallar el cambio que las originó. Nadie recibe aviso de lo que hizo él mismo.

| Hecho | Recibe el aviso |
|---|---|
| Mensaje nuevo | Quien creó el incidente, el asignado y quienes ya escribieron |
| Asignado | La persona que lo recibe |
| Cambio de estado | Quien lo creó y el asignado |
| Reabierto | Quien lo creó, el asignado y quien pidió la reapertura |
| Reapertura rechazada | Quien la pidió |
| Creado, reapertura pedida y vencido | Ver [Avisos de coordinación](coordinacion.md) |

```flujo
carriles: [API, Cola, Base de datos, Reverb, Destinatario]
pasos:
  - id: hecho
    carril: API
    tipo: inicio
    texto: Se guarda un mensaje o una entrada del historial
    detalle: 'Emite `IncidentCommented` o `IncidentActivityRecorded` tras confirmar la transacción.'
    va: oyente
  - id: oyente
    carril: Cola
    texto: CreateNotifications toma el evento
    va: quienes
  - id: quienes
    carril: Cola
    tipo: decision
    texto: ¿Hay a quién avisar?
    detalle: '`Recipients` calcula los destinatarios a partir del registro (no del incidente actual: la cola corre después y el asignado pudo cambiar). Borrar un mensaje no avisa.'
    si: guarda
    no: nada
  - id: nada
    carril: Cola
    tipo: fin
    texto: Termina sin avisos
  - id: guarda
    carril: Base de datos
    texto: Guarda un aviso por persona
    detalle: 'En `notifications`, con FK a la persona, al incidente y al mensaje o al registro del historial. Para mensajes hay un solo aviso sin leer por incidente y persona (columna calculada única): si ya existe, sube su contador y pasa a ser el más reciente.'
    va: senal
  - id: senal
    carril: Reverb
    texto: Envía notifications.changed a cada uno
    detalle: Por su canal personal, sin datos.
    va: campana
  - id: campana
    carril: Destinatario
    texto: La campana pide GET /notifications/unread
    detalle: Cuántos sin leer hay en cada pestaña.
    va: abre
  - id: abre
    carril: Destinatario
    texto: Abre el centro de notificaciones
    detalle: '`GET /notifications?category=&before=`, 20 por página. Si ya no puede ver el incidente, el aviso sale sin título ni texto.'
    va: lee
  - id: lee
    carril: Destinatario
    tipo: fin
    texto: Abre un aviso y queda leído
    detalle: '`POST /notifications/{id}/read`, o todos de una pestaña con `POST /notifications/read`. Mientras el chat de un incidente está abierto, sus avisos de mensajes se marcan leídos solos. Los avisos se borran a los 90 días.'
```

## Qué ve la persona en cada error

| Código | HTTP | Cuándo | Mensaje en pantalla |
|---|---|---|---|
| `NOTIFICATION_NOT_FOUND` | 404 | El aviso ya no existe (se borró por antigüedad) o es de otra persona | Esa notificación ya no existe: se borró por antigüedad. Actualiza la lista. |

## Dónde está en el código

- [`Recipients`](../../../backend/app/Modules/Notifications/Domain/Recipients.php): a quién se avisa de cada cosa.
- [`CreateNotifications`](../../../backend/app/Modules/Notifications/Infrastructure/CreateNotifications.php) y [`NotifyParticipants`](../../../backend/app/Modules/Notifications/Application/NotifyParticipants.php).
- [`DatabaseNotificationStore`](../../../backend/app/Modules/Notifications/Infrastructure/DatabaseNotificationStore.php): un aviso de mensajes por incidente.
- [`NotificationController`](../../../backend/app/Modules/Notifications/Http/NotificationController.php).
- [`useNotificationsStore`](../../../frontend/src/modules/notifications/application/useNotificationsStore.ts), [`NotificationBell`](../../../frontend/src/modules/notifications/presentation/NotificationBell.vue) y [`NotificationCenter`](../../../frontend/src/modules/notifications/presentation/NotificationCenter.vue).
- Explicación: [Módulo Notifications](../../backend/notifications.md).
