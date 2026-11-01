---
title: Módulo Notifications
description: Avisos personales de lo que pasa en los incidentes (mensajes, asignaciones, cambios de estado y reaperturas), cómo nacen, a quién llegan y cómo se leen.
audience: tecnico
area: backend
module: notifications
order: 10
---

# Módulo Notifications

Guarda los avisos de cada persona sobre lo que pasa en sus incidentes y los entrega al centro de notificaciones del front ([ADR-0077](../decisiones/0077-centro-de-notificaciones.md)). Cada persona solo ve y toca las suyas.

## Endpoints

Todos exigen sesión. La pestaña (`category`) es `conversations` o `activity`.

| Método | Ruta | Qué hace |
|---|---|---|
| `GET` | `/api/v1/notifications?category=&before=` | Página de 20, de la más nueva a la más vieja. `meta.unread` trae las sin leer de cada pestaña y `meta.before`, el id para pedir las anteriores (`null` si no hay más) |
| `GET` | `/api/v1/notifications/unread` | Sin leer por pestaña: `{ conversations, activity }` |
| `POST` | `/api/v1/notifications/{id}/read` | Marca una como leída. Una ajena responde `404 NOTIFICATION_NOT_FOUND`. Devuelve las sin leer |
| `POST` | `/api/v1/notifications/read` | Marca como leídas las de una pestaña (`category`) y, con `incident` (ULID), solo las de ese incidente. Devuelve las sin leer |

Cada aviso trae:

| Campo | Qué es |
|---|---|
| `id`, `type`, `category` | ULID del aviso, tipo (`comment_received`, `incident_assigned`, `status_changed`, `incident_reopened`, `reopen_rejected`) y pestaña |
| `occurrences` | Mensajes agrupados (solo en Conversaciones) |
| `actor.name` | Quién escribió o hizo el cambio |
| `incident` | `{ id, code, title }`, o `null` si la persona ya no puede verlo |
| `excerpt` | El mensaje, la nota del cambio de estado o el motivo de la reapertura (hasta 140 caracteres); `null` si el incidente no se ve |
| `status` | Nombre del estado nuevo, en cambios de estado y reaperturas |
| `comment_deleted`, `read`, `created_at` | Si el mensaje se borró, si ya se leyó y cuándo se creó |

## A quién se avisa

La regla es `Recipients` (dominio). Nadie recibe aviso de lo que hizo él mismo.

| Qué pasó | Tipo | Quién recibe |
|---|---|---|
| Mensaje nuevo en la conversación | `comment_received` | Quien creó el incidente, quien lo tiene asignado y quienes ya escribieron en ella |
| Creado con asignado, o asignado | `incident_assigned` | La persona que lo recibe |
| Cambio de estado | `status_changed` | Quien lo creó y quien lo tiene asignado |
| Reabierto | `incident_reopened` | Quien lo creó, quien lo tiene asignado y quien pidió la reapertura |
| Reapertura rechazada | `reopen_rejected` | Quien la pidió |
| Creado sin asignar | `unassigned_created` | Quienes pueden asignar (`incidents.assign`) |
| Creado con prioridad Crítica | `critical_created` | Quienes pueden asignar y quien lo atiende, si ya tiene responsable |
| Reapertura pedida | `reopen_requested` | Quienes pueden aprobarla (`incidents.reopen.approve`) |
| Venció sin resolverse | `incident_overdue` | Quien lo atiende y quienes pueden asignar |

Borrar un mensaje no avisa: el aviso que ya existía lo muestra como eliminado.

### Avisos de coordinación

Los cuatro últimos son para quienes reparten el trabajo ([ADR-0085](../decisiones/0085-avisos-de-coordinacion.md)):

- **Por permiso, nunca por rol.** `CoordinatorDirectory` (`DatabaseCoordinatorDirectory`) busca las cuentas activas con el permiso efectivo, con la misma regla de la sesión (`PermissionSql`: rol + concedidos − quitados). Solo se consulta para los registros que lo necesitan (`Recipients::COORDINATION_ACTIVITIES`: "creado" y "reapertura pedida"); un cambio de estado común no suma consultas.
- **Un aviso por persona.** Si alguien califica para varios al crear un incidente, recibe el más importante: crítico, luego asignado, luego sin asignar. Un crítico sin asignar llega como un solo aviso de crítico, con `unassigned: true`.
- **Crítico** es la prioridad con la clave `critical` (`Recipients::CRITICAL_PRIORITY`), leída del incidente al crear el aviso.
- **Vencidos.** `notifications:overdue` corre cada 5 minutos (`NotifyOverdue`). Toma los incidentes pendientes, sin eliminar, que vencieron en las últimas 24 horas y no tienen un aviso de vencido creado después de su vencimiento (`DatabaseOverdueIncidents`). Así cada vencimiento avisa una sola vez, sin marcar nada en `incidents`; si la fecha se mueve y vuelve a vencer, avisa de nuevo. Usa el índice `(status_id, due_date, deleted_at)`. El vencimiento no lo hace nadie, así que también le llega a quien creó o asignó el incidente si puede asignar.
- **`unassigned`** en la respuesta: en `critical_created`, según el registro de la creación; en `incident_overdue`, según el incidente de ahora; en `unassigned_created`, siempre.

## Cómo nacen

1. Al confirmarse la transacción salen `IncidentCommented` (un mensaje nuevo o borrado) o `IncidentActivityRecorded` (cada registro del historial; lo emite `EloquentActivityLog`).
2. `CreateNotifications` los escucha **en la cola**, así un aviso nunca demora ni hace fallar el cambio.
3. `NotifyParticipants` pide las personas (`IncidentParticipants`), aplica `Recipients`, guarda los avisos y avisa por `NotificationSignals`, que es `notifications.changed` por el canal personal ([Realtime](realtime.md)).

Como la cola corre después, el asignado de "creado" y de "asignado" sale de ese registro del historial y no del incidente de ahora. Si se tomó el incidente un segundo después, el aviso de la creación no se le atribuye a quien lo tomó. Quien pidió la reapertura sale de la solicitud que esa misma persona decidió en ese momento.

## Mensajes agrupados

Mientras el aviso de mensajes de un incidente no se lea, cada mensaje nuevo **reemplaza** ese aviso por uno más reciente que suma uno a `occurrences`. Así sube al primer lugar de la lista. Al leerlo, el siguiente mensaje abre un aviso nuevo.

El índice único `(user_id, pending_comment_incident_id)` sobre una columna calculada impide dos avisos sin leer del mismo incidente, y el bloqueo de la fila evita que dos mensajes simultáneos los dupliquen ([diccionario](../base-de-datos/diccionario/notifications.md)).

## Lo que ya no se ve

La lectura aplica a cada página la misma regla del listado (`VisibilityScope` + `VisibilitySql`). Si la persona ya no puede ver el incidente, porque se reasignó o se eliminó, el aviso llega sin incidente ni texto.

## Limpieza

`notifications:prune`, todos los días a las 3:45, borra las creadas hace más de 90 días, leídas o no (`NOTIFICATIONS_RETENTION_DAYS`). Borra de a 1.000 para no bloquear la tabla.

## Rendimiento

La página filtra por los ids de los tipos de la pestaña (`notification_type_id IN (…)`, del catálogo en memoria) y no por la categoría unida. Así MySQL recorre `(user_id, id)` hacia atrás y para en 20. Medido con 60.000 avisos (6.700 de una persona): 0,8 ms; uniendo el catálogo eran 37 ms porque ordenaba todos. Las sin leer usan `(user_id, read_at)`.

## Capas

| Capa | Pieza | Qué hace |
|---|---|---|
| Domain | `NotificationType`, `NotificationCategory` | Tipos y pestaña de cada uno |
| Domain | `Participants`, `Coordinators`, `Recipients` | Personas del incidente, quienes coordinan y a quién se avisa cada cosa |
| Application | `NotifyParticipants` | Crea los avisos de un mensaje o de un registro del historial |
| Application | `NotifyOverdue` | Crea los avisos de los incidentes que acaban de vencer |
| Application | `PersonalNotifications` | Leer, contar, marcar como leídas y limpiar |
| Application | `Ports/NotificationStore`, `Ports/IncidentParticipants`, `Ports/CoordinatorDirectory`, `Ports/OverdueIncidents`, `Ports/NotificationSignals` | Dónde se guardan, de dónde salen las personas, quienes coordinan, los vencidos y el aviso de tiempo real |
| Infrastructure | `DatabaseNotificationStore`, `DatabaseIncidentParticipants`, `DatabaseCoordinatorDirectory`, `DatabaseOverdueIncidents` | MySQL |
| Infrastructure | `CreateNotifications` | Oyente en la cola |
| Infrastructure | `Console/PruneNotificationsCommand`, `Console/NotifyOverdueCommand`, `NotificationsServiceProvider` | Limpieza diaria, aviso de vencidos cada 5 minutos y conexiones con Laravel |
| Http | `NotificationController`, `NotificationRequest`, `routes.php` | Endpoints y validación |

## Dónde está en el código

- [`backend/app/Modules/Notifications/`](../../backend/app/Modules/Notifications/)
- Eventos de origen: [`IncidentCommented.php`](../../backend/app/Modules/Incidents/Application/Events/IncidentCommented.php) y [`IncidentActivityRecorded.php`](../../backend/app/Modules/Incidents/Application/Events/IncidentActivityRecorded.php)
- Aviso de tiempo real: [`BroadcastNotificationSignals.php`](../../backend/app/Modules/Realtime/Infrastructure/BroadcastNotificationSignals.php)
- Catálogo: [`NotificationTypeSeeder.php`](../../backend/database/seeders/NotificationTypeSeeder.php)
- Tareas programadas: [`routes/console.php`](../../backend/routes/console.php)
- Pruebas: [`NotificationsTest.php`](../../backend/tests/Feature/Notifications/NotificationsTest.php), [`CoordinationNoticesTest.php`](../../backend/tests/Feature/Notifications/CoordinationNoticesTest.php) y [`RecipientsTest.php`](../../backend/tests/Unit/Notifications/RecipientsTest.php)
