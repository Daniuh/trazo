---
title: "ADR-0072: Asignarse el incidente al crearlo"
description: "Quien puede tomar incidentes marca \"Asignármelo\" al crear; solo puede elegirse a sí mismo"
audience: tecnico
area: decisiones
order: 82
---

# ADR-0072: Asignarse el incidente al crearlo

- **Estado:** Aceptada. Ajusta la regla "asignar al crear solo con `incidents.assign`" del [ADR-0043](0043-reglas-de-acceso-en-el-dominio.md).
- **Fecha:** 2026-10-31

## Contexto
Al crear un incidente, solo quien tiene `incidents.assign` (Supervisor y Super admin) veía el campo "Asignar a". Un agente que registraba algo que iba a atender él mismo tenía que crearlo, abrirlo y pulsar **Tomar**: dos pasos para algo que ya sabía al escribirlo. Mientras tanto, el incidente quedaba unos segundos sin responsable y aparecía en la cola de sin asignar de los demás.

La asignación debe seguir siendo opcional: muchas veces quien crea solo reporta y no sabe quién lo va a atender.

## Alternativas evaluadas
- **Casilla "Asignármelo" para quien puede tomar incidentes**, validada en el back con `incidents.take`.
- **Mostrar el selector "Asignar a" a los agentes**, con la lista completa.
- **Asignar siempre a quien crea, si puede tomar incidentes.**
- **Dejarlo como estaba** (crear y luego Tomar).

## Decisión
- Quien tiene `incidents.take` pero no `incidents.assign` ve, al crear, una casilla **Asignármelo**, desmarcada por defecto. Un ícono de información explica qué pasa si se marca o no.
- La API no cambia de forma: `POST /incidents` sigue recibiendo `assignee`. La regla vive en el dominio, `IncidentAccess::canAssignOnCreate`:
  - con `incidents.assign`, a cualquier persona que atiende incidentes;
  - con `incidents.take`, solo a sí mismo;
  - sin ninguno de los dos, a nadie.
- Quien no puede asignar a otros recibe siempre 403, también si el identificador no existe. Así no puede usar la API para averiguar a quién se podría asignar.
- Quien tiene `incidents.assign` sigue viendo el selector opcional, donde también puede elegirse a sí mismo.
- El historial guarda "creó el incidente" con la asignación, como ya pasaba al asignar al crear, y lo muestra como "creó el incidente y se lo asignó". No se avisa por tiempo real "Te asignaron…" a quien se asignó a sí mismo.

## Alternativas descartadas
- **El selector completo para los agentes:** "Asignar a otros" es una función de quien coordina. Darla al crear abriría una puerta que el permiso cierra en el resto de la aplicación.
- **Asignar siempre a quien crea:** rompe el caso más común del agente que solo registra lo que le reportan por teléfono, y obliga a quitar la asignación después.
- **Dejarlo como estaba:** funciona, pero obliga a un paso extra y deja el incidente un momento en la cola de los demás, donde otra persona podría tomarlo.

## Consecuencias
- Un agente crea y se queda con el incidente en un solo paso.
- No hay permiso nuevo: "tomar" ya significa quedarse con un incidente sin asignar, y uno recién creado lo está.
- El Solicitante y el Administrador no ven nada nuevo, porque no atienden incidentes.

## Dónde está en el código
- Regla: [`IncidentAccess.php`](../../backend/app/Modules/Incidents/Domain/IncidentAccess.php) (`canAssignOnCreate`) y [`CreateIncident.php`](../../backend/app/Modules/Incidents/Application/CreateIncident.php).
- Front: [`IncidentForm.vue`](../../frontend/src/modules/incidents/presentation/components/IncidentForm.vue) (`selfAssignId`), [`IncidentFormPage.vue`](../../frontend/src/modules/incidents/presentation/IncidentFormPage.vue) y [`historyText.ts`](../../frontend/src/modules/incidents/presentation/historyText.ts).
- Pruebas: [`CreateIncidentTest.php`](../../backend/tests/Feature/Incidents/CreateIncidentTest.php), [`IncidentAccessTest.php`](../../backend/tests/Unit/Incidents/IncidentAccessTest.php) e [`incidents.spec.ts`](../../frontend/e2e/incidents.spec.ts).
