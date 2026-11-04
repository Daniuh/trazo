---
title: "ADR-0077: Centro de notificaciones"
description: "Una campana con dos pestañas (Conversaciones y Actividad) avisa a cada persona lo que pasa en sus incidentes; el perfil pasa al pie del menú lateral"
audience: tecnico
area: decisiones
tags: [producto]
order: 87
---

# ADR-0077: Centro de notificaciones

- **Estado:** Aceptada. Segundo bloque de la conversación y el centro de notificaciones. El lugar del perfil lo reemplaza el [ADR-0079](0079-menu-en-dock.md): ahora es un círculo con las iniciales en el dock.
- **Fecha:** 2026-10-31

## Contexto
Con la [conversación de cada incidente](0076-conversacion-del-incidente.md), quien escribía una pregunta no tenía cómo enterarse de la respuesta sin volver a abrir el incidente. Lo mismo pasaba con lo demás: el aviso "Te asignaron…" solo aparecía si la persona estaba conectada en ese momento y desaparecía a los pocos segundos. Cambios de estado y respuestas a una reapertura no avisaban nada. Hacía falta un lugar que guardara esos avisos y los mostrara hasta que la persona los viera.

## Alternativas evaluadas
Para guardar los avisos:

- **Tabla propia `notifications` con claves foráneas** al mensaje o al registro del historial que la originó, más un catálogo `notification_types`.
- **La tabla de notificaciones de Laravel** (`notifiable_type`/`notifiable_id` y el contenido en JSON).
- **Calcularlos al vuelo** desde el historial y la conversación, sin guardar nada.

Para mostrarlos:

- **Un panel** (`AppSheet`): desde la derecha en portátil y monitor y desde abajo en celular y tablet, como la conversación.
- **Un menú desplegable bajo la campana.**
- **Una pantalla propia** ("Notificaciones") en el menú lateral.

Para los mensajes de una conversación:

- **Un aviso por incidente** que se agrupa mientras no se lea ("3 mensajes nuevos").
- **Un aviso por mensaje.**

## Decisión
- **Campana** a la derecha del encabezado en las cuatro vistas, con las sin leer (hasta "99+"). Abre un **panel** con dos pestañas:
  - **Conversaciones:** mensajes nuevos en los incidentes en los que la persona participa. Llevan al incidente con la conversación abierta.
  - **Actividad:** "te asignaron", cambio de estado, reabierto y reapertura rechazada. Llevan al incidente.
- **A quién se avisa** (`Recipients`, dominio). Nunca a quien hizo el cambio.
  - **Mensaje:** a quien creó el incidente, a quien lo tiene asignado y a quienes ya escribieron.
  - **Asignado:** a quien lo recibe.
  - **Cambio de estado:** a quien lo creó y a quien lo tiene asignado.
  - **Reabierto:** a ellos dos y a quien pidió la reapertura.
  - **Reapertura rechazada:** a quien la pidió.
- **Datos:** `notification_types` (código, nombre y pestaña) y `notifications`, con claves foráneas a la persona, al incidente y al mensaje o al registro del historial. No copian textos: el nombre de quien actuó, el mensaje o la nota salen de su origen. Sin relaciones polimórficas.
- **Mensajes agrupados:** mientras un aviso de mensajes no se lea, el siguiente mensaje del mismo incidente lo reemplaza por uno más reciente que los cuenta (`occurrences`). Lo garantiza un índice único sobre una columna calculada, como en las reaperturas pendientes.
- **Cómo nacen:** los mensajes (`IncidentCommented`) y el historial (`IncidentActivityRecorded`, nuevo) emiten eventos al confirmarse. Un oyente **en la cola** crea los avisos y manda `notifications.changed` por el canal personal. Crear avisos nunca demora ni hace fallar el cambio que los originó.
- **Leer:** abrir un aviso lo marca como leído; "Marcar todo como leído" por pestaña; tener abierta la conversación de un incidente da por leídos sus mensajes, también los que lleguen mientras tanto.
- **Lo que ya no se ve:** si la persona perdió acceso al incidente, el aviso queda sin código, título ni mensaje ("Ya no tienes acceso a este incidente"). Es la misma regla de visibilidad del listado.
- **Retención:** 90 días, leídas o no (`NOTIFICATIONS_RETENTION_DAYS`, limpieza diaria `notifications:prune`).
- **Perfil al pie del menú lateral:** iniciales, nombre y rol, y al lado dos botones de solo ícono (Mi cuenta y Cerrar sesión, con su nombre al pasar el cursor). Cerrar sesión pide confirmación, por si se tocó sin querer. En tablet y con el menú plegado, en columna: iniciales y los dos botones. En celular, al final del menú hamburguesa. Así el encabezado queda con el logo y la campana. Se descartó un menú desplegable desde el perfil: un toque más para lo mismo y un menú flotando sobre el contenido.
- **Paneles de borde a borde:** desde la derecha, de arriba abajo y sin esquinas redondeadas, como el menú lateral; desde abajo, a todo lo ancho. Vale para todos los `AppSheet` (también la conversación).

## Alternativas descartadas
- **Tabla de Laravel:** es polimórfica (`notifiable_type`) y guarda el aviso como JSON. Rompe la regla de claves foráneas reales y repite datos que ya existen en el historial.
- **Calcular al vuelo:** "leído" no existe en el historial, y cada apertura de la campana cruzaría historial y conversación de todos los incidentes de la persona.
- **Menú desplegable:** en celular queda estrecho y no cabe una lista que se desplaza. El panel es el mismo patrón de la conversación, así que la persona ya lo conoce.
- **Pantalla propia:** obliga a salir de lo que se está haciendo para ver un aviso.
- **Un aviso por mensaje:** una conversación activa llenaría la campana de avisos del mismo incidente.

## Consecuencias
- Nada se pierde por no estar conectado: los avisos esperan en la campana.
- El aviso emergente "Te asignaron…" se mantiene para quien está conectado; la campana lo guarda además.
- Los avisos de coordinación (creado sin asignar, crítico creado, reapertura pendiente y vencido) se sumaron a Actividad en el [ADR-0085](0085-avisos-de-coordinacion.md).
- Medido con 60.000 avisos (6.700 de una persona): la página de 20 tarda 0,8 ms porque filtra por los ids de los tipos y recorre `(user_id, id)` hacia atrás; uniendo el catálogo tardaba 37 ms.
- Quedan como mejoras: avisos por correo o push y preferencias por tipo de aviso.

## Dónde está en el código
- Reglas: [`Recipients.php`](../../backend/app/Modules/Notifications/Domain/Recipients.php) y [`NotificationType.php`](../../backend/app/Modules/Notifications/Domain/NotificationType.php).
- Casos de uso: [`NotifyParticipants.php`](../../backend/app/Modules/Notifications/Application/NotifyParticipants.php) y [`PersonalNotifications.php`](../../backend/app/Modules/Notifications/Application/PersonalNotifications.php).
- Datos: [`DatabaseNotificationStore.php`](../../backend/app/Modules/Notifications/Infrastructure/DatabaseNotificationStore.php), [`DatabaseIncidentParticipants.php`](../../backend/app/Modules/Notifications/Infrastructure/DatabaseIncidentParticipants.php) y las migraciones [`000041`](../../backend/database/migrations/2026_11_01_000041_create_notification_types_table.php) y [`000042`](../../backend/database/migrations/2026_11_01_000042_create_notifications_table.php).
- Front: [`useNotificationsStore.ts`](../../frontend/src/modules/notifications/application/useNotificationsStore.ts), [`NotificationBell.vue`](../../frontend/src/modules/notifications/presentation/NotificationBell.vue), [`NotificationCenter.vue`](../../frontend/src/modules/notifications/presentation/NotificationCenter.vue) y [`AppUserMenu.vue`](../../frontend/src/app/layout/AppUserMenu.vue).
- Pruebas: [`NotificationsTest.php`](../../backend/tests/Feature/Notifications/NotificationsTest.php), [`RecipientsTest.php`](../../backend/tests/Unit/Notifications/RecipientsTest.php), [`notifications.spec.ts`](../../frontend/src/modules/notifications/notifications.spec.ts) y la E2E [`notifications.spec.ts`](../../frontend/e2e/notifications.spec.ts).
