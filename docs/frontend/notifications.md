---
title: Módulo de notificaciones (frontend)
description: Campana del dock y centro de notificaciones con las pestañas Conversaciones y Actividad, y cómo se ponen al día.
audience: tecnico
area: frontend
module: notifications
order: 10
---

# Módulo de notificaciones (frontend)

La campana del dock y el panel con los avisos de cada persona. **El front no decide a quién se avisa:** solo muestra lo que devuelve la API ([Módulo Notifications](../backend/notifications.md)).

## Cómo se ve

- **Campana** al final del dock, junto a la cuenta, en las cuatro vistas. Con avisos sin leer muestra un contador rojo con texto blanco (tono `danger`), hasta "99+". Su nombre accesible dice cuántos hay: "Notificaciones, 3 sin leer".
- **Panel** (`AppSheet`): desde la derecha en portátil y monitor, desde abajo en celular y tablet.
  - Arriba, las pestañas **Conversaciones** y **Actividad** (`AppSegmented`), cada una con sus sin leer. Abre en Conversaciones, salvo que solo Actividad tenga algo nuevo.
  - Cada aviso muestra qué pasó ("Laura Gómez escribió", "3 mensajes nuevos, el último de…", "lo pasó a Resuelto"), el código y el título del incidente, lo que se dijo (el mensaje, la nota o el motivo, en una línea) y hace cuánto.
  - Sin leer: punto azul y texto en negrita. Para lectores de pantalla, "Sin leer." oculto al inicio.
  - "Ver anteriores" pide la página siguiente; al pie, "Marcar todo como leído" para la pestaña, si tiene algo sin leer.
  - Vacío: "No hay mensajes" o "No hay novedades", con una línea que explica qué llega ahí.
- **Abrir un aviso** lo marca como leído, cierra el panel y lleva al incidente. Si es un mensaje, llega con `?conversacion=1`: el detalle baja hasta la actividad y deja el foco en el campo para escribir, también si ya se estaba en ese incidente.
- **Incidente que ya no se ve:** el aviso queda sin enlace, con "Ya no tienes acceso a este incidente".

## Cómo se pone al día

| Cuándo | Qué hace |
|---|---|
| Al entrar (la campana se monta con el shell) | Pide las sin leer (`start`, que además limpia todo si cambió la persona de la pestaña) |
| Al abrir el panel o cambiar de pestaña | Pide esa pestaña si no la tiene vigente (`ensure`) |
| Llega `notifications` por el canal personal | Vuelve a pedir las sin leer y marca las listas como vencidas; si el panel está abierto, pide de nuevo la pestaña que se ve |
| `reconnected` | Igual, por si se perdió un aviso mientras estaba desconectado |
| Conversación de un incidente abierta | Sus mensajes cuentan como leídos (`watchConversation`): al abrirla y con cada aviso que llega mientras siga abierta |

Marcar como leído responde con las sin leer nuevas, así la campana no vuelve a preguntar. Si marcar uno falla, el aviso igual lleva a su incidente.

## Capas

| Capa | Pieza | Qué hace |
|---|---|---|
| domain | `notification.ts` | Tipos, textos de cada aviso (`headline`, `detail`), a dónde lleva (`target`) y el contador (`badgeText`) |
| infrastructure | `notificationsApi.ts` | Endpoints y traducción de la respuesta |
| application | `useNotificationsStore` (Pinia) | Sin leer, listas por pestaña, marcar como leídas y la conversación abierta |
| presentation | `NotificationBell`, `NotificationCenter`, `NotificationItem` | Campana, panel y cada aviso |

Mensajes de error en `errors.ts` (`NOTIFICATION_NOT_FOUND`).

## Dónde está en el código

- [`frontend/src/modules/notifications/`](../../frontend/src/modules/notifications/)
- En el dock: [`AppShell.vue`](../../frontend/src/app/layout/AppShell.vue). Conversación leída: [`IncidentDetailPage.vue`](../../frontend/src/modules/incidents/presentation/IncidentDetailPage.vue)
- Pruebas: [`notifications.spec.ts`](../../frontend/src/modules/notifications/notifications.spec.ts) y [`e2e/notifications.spec.ts`](../../frontend/e2e/notifications.spec.ts)
- Decisión: [ADR-0077](../decisiones/0077-centro-de-notificaciones.md)
