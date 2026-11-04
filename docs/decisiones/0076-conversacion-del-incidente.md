---
title: "ADR-0076: Conversación del incidente"
description: "Cada incidente tiene una conversación tipo chat; quien escribió un mensaje puede borrarlo durante 5 minutos"
audience: tecnico
area: decisiones
tags: [producto]
order: 86
---

# ADR-0076: Conversación del incidente

- **Estado:** Aceptada. Primer bloque de la conversación y el centro de notificaciones. El panel de la conversación se reemplazó por la actividad del detalle ([ADR-0083](0083-detalle-con-camino-y-actividad.md)); las reglas siguen igual.
- **Fecha:** 2026-10-31

## Contexto
Cuando quien atiende un incidente necesitaba un dato ("¿desde qué equipo pasa?") o quien lo reportó quería saber cómo iba, la única salida era otro canal: un chat, un correo o una llamada. Esa conversación quedaba fuera de Trazo, sin relación con el incidente y sin rastro para quien lo tomara después. La nota al cambiar de estado no sirve para eso: es una sola línea ligada a un cambio, no un ida y vuelta.

## Alternativas evaluadas
- **Conversación tipo chat en cada incidente**, en un panel, con mensajes en su propia tabla.
- **Mensajes dentro del historial** (`incident_activities`), como un tipo más.
- **Comentarios largos tipo foro**, con edición y respuestas anidadas.
- **Seguir con canales externos** y enlazar el incidente desde ahí.

Y para borrar o editar mensajes:

- **Borrar el propio durante 5 minutos**, sin editar.
- **Ni editar ni borrar.**
- **Editar y borrar el propio sin límite.**

## Decisión
- Cada incidente tiene una **conversación** en un panel: desde la derecha en portátil y monitor y desde abajo en celular y tablet. El botón "Conversación (N)" va al lado del botón principal del detalle.
- **Quién:** lee quien puede ver el incidente; escribe quien además tiene `incidents.comment` (todos los roles base). Se puede escribir también en un incidente terminado, porque un seguimiento no cambia su estado. En uno eliminado, no.
- **Datos:** tabla `incident_comments` con claves foráneas reales, ULID público y mensajes de hasta 2.000 caracteres. Los mensajes no se anotan en el historial, para que el historial siga contando solo los cambios del incidente.
- **Borrar:** solo el propio y solo durante 5 minutos. La fila queda con el texto vacío y la conversación muestra "Mensaje eliminado". No se editan mensajes.
- **Tiempo real:** escribir y borrar emiten `IncidentCommented`, que sale como `incident.commented` (`{id, change}`, sin el texto) por los mismos canales que un cambio del incidente. Quien lo recibe vuelve a pedir la conversación a la API, que aplica sus permisos.
- **Límite:** 20 mensajes por minuto por persona.
- **Lectura:** de a 50 mensajes, del más nuevo al más viejo, con `before` (el ULID del último recibido) para pedir los anteriores.

## Alternativas descartadas
- **Mensajes en el historial:** mezclaría conversación con cambios. El historial se volvería ruidoso y perdería su valor de rastro de lo que le pasó al incidente.
- **Comentarios tipo foro:** más pesado de leer y de usar para lo que se necesita, que son preguntas y respuestas cortas.
- **Canales externos:** es justo lo que se quería evitar: la información queda fuera del incidente.
- **Ni editar ni borrar:** deja sin salida un error evidente, como escribir en el incidente equivocado o pegar algo que no debía.
- **Editar y borrar sin límite:** cualquiera podría cambiar lo que otros ya leyeron o respondieron, y la conversación dejaría de ser confiable.

## Consecuencias
- Las preguntas y respuestas de un caso quedan en el caso, para quien lo atienda después.
- El centro de notificaciones (siguiente bloque) se apoya en este mismo evento para avisar a quien participa.
- Quedan como mejoras: adjuntos, menciones con @ y aviso por correo de cada mensaje.

## Dónde está en el código
- Reglas: [`IncidentAccess.php`](../../backend/app/Modules/Incidents/Domain/IncidentAccess.php) (`IncidentAction::Comment`) y [`CommentRules.php`](../../backend/app/Modules/Incidents/Domain/CommentRules.php).
- Casos de uso y datos: [`IncidentConversation.php`](../../backend/app/Modules/Incidents/Application/IncidentConversation.php), [`DatabaseIncidentComments.php`](../../backend/app/Modules/Incidents/Infrastructure/DatabaseIncidentComments.php) y la migración [`000040`](../../backend/database/migrations/2026_11_01_000040_create_incident_comments_table.php).
- API: [`IncidentCommentController.php`](../../backend/app/Modules/Incidents/Http/IncidentCommentController.php). Tiempo real: [`BroadcastIncidentComment.php`](../../backend/app/Modules/Realtime/Infrastructure/BroadcastIncidentComment.php).
- Front: [`useConversation.ts`](../../frontend/src/modules/incidents/application/useConversation.ts). El panel y el botón se reemplazaron por la actividad del detalle ([ADR-0083](0083-detalle-con-camino-y-actividad.md)) y volvieron como un panel de chat y actividad con el botón "Chat" en la ficha: [`IncidentActivityPanel.vue`](../../frontend/src/modules/incidents/presentation/detail/IncidentActivityPanel.vue) ([ADR-0084](0084-detalle-con-historial-y-panel-de-chat.md)).
- Pruebas: [`IncidentConversationTest.php`](../../backend/tests/Feature/Incidents/IncidentConversationTest.php), [`CommentRulesTest.php`](../../backend/tests/Unit/Incidents/CommentRulesTest.php), [`conversation.spec.ts`](../../frontend/src/modules/incidents/application/conversation.spec.ts) y la E2E [`conversation.spec.ts`](../../frontend/e2e/conversation.spec.ts).
