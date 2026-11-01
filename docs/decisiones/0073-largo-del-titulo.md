---
title: "ADR-0073: Largo del título de un incidente"
description: "El título va de 5 a 80 caracteres, para que sea concreto y quepa en una línea"
audience: tecnico
area: decisiones
order: 83
---

# ADR-0073: Largo del título de un incidente

- **Estado:** Aceptada. Ajusta el máximo de 150 caracteres de la validación de incidentes ([ADR-0043](0043-reglas-de-acceso-en-el-dominio.md)).
- **Fecha:** 2026-10-31

## Contexto
El título admitía hasta 150 caracteres. Con títulos de ese largo, el encabezado del detalle se partía en varias líneas, la tabla y las tarjetas los cortaban, y el aviso "Te asignaron TZ-…" quedaba ilegible. Además, un título largo suele ser una descripción mal ubicada: el título debe decir en pocas palabras qué pasa, y el detalle va en la descripción.

En el otro extremo, nada impedía títulos como "x" o "falla", que no sirven para buscar ni para reconocer el incidente en un listado.

## Alternativas evaluadas
- **De 5 a 80 caracteres**, con contador en el formulario.
- **Mantener 150.**
- **Un límite de 60.**
- **Un límite de 100 o 120.**
- **Contar palabras en vez de caracteres.**

## Decisión
- El título va de **5 a 80 caracteres**, contados sin los espacios de los extremos. El back lo valida en `IncidentRequest` (`TITLE_MIN`, `TITLE_MAX`) y responde `TOO_SHORT` o `TOO_LONG` con su parámetro.
- El formulario muestra un contador "usados/80" bajo el campo (`AppTextField` con `counter`), no deja escribir más allá del límite y avisa antes de enviar con el mismo mensaje de la API. El front solo acompaña: la regla vive en el back.
- La columna `incidents.title` sigue en `VARCHAR(150)`. Las migraciones son solo aditivas y el límite real lo pone la validación, así que no hace falta tocarla.
- Los incidentes que ya pasan de 80 caracteres se siguen viendo igual. Al editarlos, piden acortar el título antes de guardar.

## Alternativas descartadas
- **Mantener 150:** es el origen del problema; deja el largo a criterio de cada persona.
- **60:** se queda corto para títulos útiles con un lugar o un sistema ("La red wifi de la sala de juntas se congela al abrir archivos grandes" tiene 69).
- **100 o 120:** ya no caben en una línea de una tarjeta de celular ni del aviso, y vuelven a invitar a escribir la descripción en el título.
- **Contar palabras:** es más difícil de explicar y de medir en el formulario, y no evita palabras largas o pegadas.

## Consecuencias
- Los títulos caben en una línea del listado, de las tarjetas y de los avisos, y se leen de un vistazo.
- Quien escribe ve cuánto le queda mientras escribe.
- Si en el futuro se quiere otro límite, se cambia en un solo lugar del back y en la constante del front que acompaña al formulario.

## Dónde está en el código
- Regla: [`IncidentRequest.php`](../../backend/app/Modules/Incidents/Http/Requests/IncidentRequest.php) (`TITLE_MIN`, `TITLE_MAX`).
- Front: [`incident.ts`](../../frontend/src/modules/incidents/domain/incident.ts) (`TITLE_MIN`, `TITLE_MAX`), [`useIncidentForm.ts`](../../frontend/src/modules/incidents/application/useIncidentForm.ts), [`IncidentForm.vue`](../../frontend/src/modules/incidents/presentation/components/IncidentForm.vue) y [`AppTextField.vue`](../../frontend/src/shared/ui/form/AppTextField.vue) (`counter`).
- Pruebas: [`CreateIncidentTest.php`](../../backend/tests/Feature/Incidents/CreateIncidentTest.php) y [`application.spec.ts`](../../frontend/src/modules/incidents/application/application.spec.ts).
