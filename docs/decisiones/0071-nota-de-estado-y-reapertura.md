---
title: "ADR-0071: Nota al cambiar de estado y reapertura con aprobación"
description: "Resuelto y Cancelado exigen una nota; lo cerrado o cancelado solo se reabre con motivo y la aprobación de quien supervisa"
audience: tecnico
area: decisiones
order: 81
---

# ADR-0071: Nota al cambiar de estado y reapertura con aprobación

- **Estado:** Aceptada. Complementa al [ADR-0043](0043-reglas-de-acceso-en-el-dominio.md).
- **Fecha:** 2026-10-31

## Contexto
Hasta ahora, cambiar de estado solo recibía el estado nuevo. El historial decía "pasó a Resuelto", pero no cómo se resolvió, y una cancelación no dejaba su motivo. A quien reportó y a quien supervisa les sirve más saber qué se hizo que saber que se hizo algo.

Cerrado y Cancelado son estados finales y no tenían salida. Si el problema volvía o algo se cerró por error, había que crear un incidente nuevo y se perdía el hilo. Pero dejar que cualquiera cambie un incidente cerrado también es un problema: los cierres dejarían de significar algo y las métricas no serían confiables.

## Alternativas evaluadas
- **Nota por cambio de estado**, guardada en el historial, obligatoria donde el catálogo lo pida.
- **Módulo de comentarios:** una conversación por incidente, aparte de los cambios de estado.
- **Reabrir con solicitud y aprobación:** quien atiende o reportó pide reabrir con un motivo, y quien supervisa aprueba o rechaza.
- **Reabrir directo con permiso:** solo quien supervisa reabre, con motivo, sin solicitudes.
- **Transiciones de salida en el catálogo** (Cerrado → En progreso) para cualquiera que cambie estados.

## Decisión
- **Nota al cambiar de estado:**
  - Se guarda en una columna propia, `incident_activities.note` (hasta 1.000 caracteres), y no dentro del JSON de cambios, para que se lea y se consulte sin desarmarlo.
  - Es obligatoria al llegar a los estados marcados con `statuses.requires_note` (Resuelto y Cancelado). Es un dato del catálogo: cambiarla no exige tocar el código.
  - En los demás cambios es opcional. Si falta donde se exige, la API responde 422 `STATUS_NOTE_REQUIRED`.
- **Reapertura con aprobación:**
  - Quien puede ver el incidente terminado y tiene `incidents.reopen.request` (todos los roles base) pide reabrirlo con un motivo obligatorio.
  - Quien tiene `incidents.reopen.approve` (Supervisor y Super admin) aprueba o rechaza. Rechazar exige un motivo. Nadie decide su propia solicitud (`REOPEN_OWN_REQUEST`).
  - Quien puede aprobar también reabre directamente, con su motivo.
  - Solo hay **una solicitud pendiente por incidente**. Lo garantiza la base con una columna calculada (`pending_incident_id`) y un índice único.
  - **Al reabrir**, el incidente vuelve a En progreso si tiene a alguien asignado, o al estado inicial si no. `resolved_at` se borra y recibe un vencimiento nuevo según su prioridad.
  - El motivo queda como nota en el historial: el de quien pidió, si se aprobó una solicitud.
- **El historial del detalle muestra los 5 cambios más recientes.** El resto se ve en un panel, que pide las páginas que falten.
- **El listado filtra los que tienen una solicitud pendiente** (`filter[reopen_requested]=1`), para que quien supervisa los encuentre.

## Alternativas descartadas
- **Módulo de comentarios:** cubre más casos, pero suma una tabla, permisos, avisos y su propia pantalla, y la prueba técnica no lo pide. La nota resuelve lo que hacía falta (explicar cada cambio) sin abrir otro módulo. Sigue en [mejoras propuestas](../mejoras/propuestas.md).
- **Reabrir directo, sin solicitudes:** quien atiende tendría que pedirlo por fuera de la aplicación, y no quedaría rastro de la petición ni de un rechazo.
- **Transiciones de salida en el catálogo:** cualquiera con permiso de cambiar estados podría reabrir sin explicar ni pedir. Un cierre dejaría de ser definitivo.
- **Guardar la nota en `changes`:** mezcla lo que cambió con lo que la persona escribió, y obliga a leer el JSON para mostrarla o buscarla.

## Consecuencias
- Cada resolución y cada cancelación explica qué pasó. La auditoría de incidentes muestra la nota de cada registro.
- Cerrado y Cancelado siguen siendo finales para el flujo normal. La reapertura es un camino aparte, controlado y con registro: `reopen_requested`, `reopen_rejected` y `reopened`.
- Pedir y rechazar no cambian columnas del incidente (no sube su versión), pero sí avisan en tiempo real, para que quien supervisa vea la solicitud sin recargar.
- La solicitud no se puede retirar, por ahora. Si hiciera falta, sería una acción más sobre la misma tabla.
- Los datos de demostración traen notas de ejemplo en lo resuelto y lo cancelado.

## Dónde está en el código
- [`backend/app/Modules/Incidents/Domain/IncidentAccess.php`](../../backend/app/Modules/Incidents/Domain/IncidentAccess.php): acciones `request_reopen`, `reopen` y `decide_reopen`.
- [`ChangeIncidentStatus.php`](../../backend/app/Modules/Incidents/Application/ChangeIncidentStatus.php), [`RequestIncidentReopen.php`](../../backend/app/Modules/Incidents/Application/RequestIncidentReopen.php), [`ReopenIncident.php`](../../backend/app/Modules/Incidents/Application/ReopenIncident.php) y [`RejectIncidentReopen.php`](../../backend/app/Modules/Incidents/Application/RejectIncidentReopen.php).
- [`EloquentReopenRequests.php`](../../backend/app/Modules/Incidents/Infrastructure/EloquentReopenRequests.php) y [`IncidentReopenController.php`](../../backend/app/Modules/Incidents/Http/IncidentReopenController.php).
- Migraciones `000035` a `000037` en [`backend/database/migrations/`](../../backend/database/migrations/).
- Front: [`IncidentNoteDialog.vue`](../../frontend/src/modules/incidents/presentation/components/IncidentNoteDialog.vue), [`ReopenRequestNotice.vue`](../../frontend/src/modules/incidents/presentation/components/ReopenRequestNotice.vue). El historial ahora vive en el detalle, con lo reciente en la página y todo en el panel de actividad: [`IncidentHistory.vue`](../../frontend/src/modules/incidents/presentation/detail/IncidentHistory.vue) e [`IncidentActivityPanel.vue`](../../frontend/src/modules/incidents/presentation/detail/IncidentActivityPanel.vue) ([ADR-0083](0083-detalle-con-camino-y-actividad.md), [ADR-0084](0084-detalle-con-historial-y-panel-de-chat.md)).
- Pruebas: [`ReopenIncidentTest.php`](../../backend/tests/Feature/Incidents/ReopenIncidentTest.php), [`IncidentLifecycleTest.php`](../../backend/tests/Feature/Incidents/IncidentLifecycleTest.php) y [`incidents.spec.ts`](../../frontend/e2e/incidents.spec.ts).
