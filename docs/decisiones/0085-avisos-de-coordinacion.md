---
title: "ADR-0085: Avisos de coordinación para quienes reparten el trabajo"
description: "Nuevo sin asignar, crítico reportado, reapertura pendiente y vencido llegan a quienes coordinan, por permiso y una sola vez"
audience: tecnico
area: decisiones
tags: [producto]
order: 95
---

# ADR-0085: Avisos de coordinación para quienes reparten el trabajo

- **Estado:** Aceptada. Completa el centro de notificaciones del [ADR-0077](0077-centro-de-notificaciones.md).
- **Fecha:** 2026-11-01

## Contexto
El centro de notificaciones avisaba a quienes participan en un incidente: quien lo creó, quien lo atiende y quienes escriben en su chat. Quien reparte el trabajo no se enteraba de lo que necesita su atención sin abrir el listado: un incidente que llegó y nadie tomó, uno crítico, una reapertura que espera su decisión o uno que venció.

## Alternativas evaluadas
Para elegir quién recibe:

- Por rol (Supervisor y Super admin).
- Por permiso efectivo (`incidents.assign` e `incidents.reopen.approve`).
- A todo el que pueda ver el incidente.

Para detectar los vencidos:

- Una tarea programada que revisa cada pocos minutos.
- Calcularlos al abrir el centro de notificaciones.
- Una columna `overdue_notified_at` en `incidents`.

## Decisión
- **Cuatro tipos nuevos**, en la pestaña Actividad: `unassigned_created`, `critical_created`, `reopen_requested` e `incident_overdue`.
- **Por permiso:**
  - "Nuevo sin asignar" y "crítico" van a quienes tienen `incidents.assign`.
  - "Reapertura pendiente", a quienes tienen `incidents.reopen.approve`.
  - "Vencido", a quien lo atiende y a quienes tienen `incidents.assign`.
  - Solo cuentas activas, con la misma regla de permisos efectivos de la sesión (`PermissionSql`).
- **Un aviso por persona y por hecho**, el más importante: crítico, luego asignado, luego sin asignar. Un crítico sin asignar llega como un solo aviso que lo dice.
- **Nunca a quien hizo la acción**, como los demás avisos. El vencimiento no lo hace nadie, así que llega a todos los que corresponde.
- **Vencidos con una tarea cada 5 minutos** (`notifications:overdue`). Mira lo que venció en las últimas 24 horas y no tiene un aviso de vencido creado después de su vencimiento.
- **Los agentes no reciben "nuevo sin asignar":** ya ven los incidentes sin responsable en su listado y en "Mis incidentes".

## Alternativas descartadas
- **Por rol:** las reglas del proyecto consultan permisos, nunca nombres de rol. Con permisos, si a una persona se le concede asignar, empieza a recibirlos sin tocar código; si se le quita, deja de recibirlos.
- **A todo el que pueda verlo:** a un agente le llegaría un aviso por cada incidente nuevo. El ruido termina en que nadie mira la campana.
- **Calcular vencidos al abrir la campana:** el aviso llegaría solo cuando alguien la abre, y sin tiempo real.
- **Columna `overdue_notified_at`:** guarda en `incidents` un dato que es de las notificaciones, y hay que acordarse de limpiarlo al mover la fecha o reabrir. Preguntar si existe el aviso da lo mismo sin tocar esa tabla.

## Consecuencias
- Supervisor y Super admin reciben más avisos: uno por cada incidente sin asignar y por cada vencimiento. Si en la operación real resultan muchos, la mejora propuesta es que cada persona pueda silenciar tipos de aviso.
- Si la tarea programada estuvo detenida más de un día, lo que venció antes no se avisa de golpe.
- El aviso de vencido no tiene registro del historial detrás, así que no muestra quién lo hizo: no lo hizo nadie.
- "Crítico" es la prioridad de clave `critical`. Si el catálogo cambia esa clave, hay que cambiar `Recipients::CRITICAL_PRIORITY`.

## Dónde está en el código
- Reglas: [`Recipients.php`](../../backend/app/Modules/Notifications/Domain/Recipients.php) y [`Coordinators.php`](../../backend/app/Modules/Notifications/Domain/Coordinators.php).
- Casos de uso: [`NotifyParticipants.php`](../../backend/app/Modules/Notifications/Application/NotifyParticipants.php) y [`NotifyOverdue.php`](../../backend/app/Modules/Notifications/Application/NotifyOverdue.php).
- Consultas: [`DatabaseCoordinatorDirectory.php`](../../backend/app/Modules/Notifications/Infrastructure/DatabaseCoordinatorDirectory.php) y [`DatabaseOverdueIncidents.php`](../../backend/app/Modules/Notifications/Infrastructure/DatabaseOverdueIncidents.php).
- Tarea: [`NotifyOverdueCommand.php`](../../backend/app/Modules/Notifications/Infrastructure/Console/NotifyOverdueCommand.php) y [`routes/console.php`](../../backend/routes/console.php).
- Textos en el front: [`notification.ts`](../../frontend/src/modules/notifications/domain/notification.ts).
- Pruebas: [`CoordinationNoticesTest.php`](../../backend/tests/Feature/Notifications/CoordinationNoticesTest.php), [`RecipientsTest.php`](../../backend/tests/Unit/Notifications/RecipientsTest.php) y [`notifications.spec.ts`](../../frontend/e2e/notifications.spec.ts).
