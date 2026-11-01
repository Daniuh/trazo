---
title: "ADR-0082: Formulario del incidente que guía mientras se escribe"
description: "La prioridad se elige en tarjetas con su plazo, el vencimiento con atajos, al crear se muestran incidentes parecidos y una vista previa dice cómo se verá"
audience: tecnico
area: decisiones
order: 92
---

# ADR-0082: Formulario del incidente que guía mientras se escribe

- **Estado:** Aceptada. Cambia la presentación del formulario del [ADR-0043](0043-reglas-de-acceso-en-el-dominio.md); sigue siendo un solo formulario para crear y editar, y la API no cambia.
- **Fecha:** 2026-10-31

## Contexto
El formulario tenía título y descripción en una tarjeta y, al lado, prioridad, vencimiento y asignación como selectores. Es la forma de casi cualquier formulario. Quien reporta no veía qué plazo pedía al elegir una prioridad, y nada le avisaba si su problema ya estaba reportado.

## Alternativas evaluadas
- **Dejarlo como estaba**, con mejor estilo.
- **Un formulario por pasos** (qué pasa, qué tan grave, cuándo).
- **Un solo formulario que guía:**
  - Prioridad en tarjetas con su tiempo de resolución y cuándo usarla.
  - Vencimiento con atajos: sugerido, hoy, mañana u otra fecha.
  - "¿Ya lo reportaron?" con incidentes parecidos al escribir el título.
  - Vista previa de cómo se verá en la lista.

Para las ayudas de cada prioridad ("Detiene la operación"):

- **Fijas en el front**, por código de prioridad.
- **Como dato del catálogo**, con una columna nueva en `priorities`.

## Decisión
- **Un solo formulario que guía**, para crear y editar.
- **Prioridad en tarjetas** (`PriorityPicker`): radios reales con nombre, punto de color, "4 h para resolver" (del catálogo) y una ayuda corta. Se elige con clic o con las flechas.
- **Vencimiento con atajos** (`DuePicker`): "Sugerido" (solo al crear: deja el campo vacío y la API lo calcula con la prioridad), "Hoy" y "Mañana" a las 6:00 p. m. (fin de jornada) y "Otra fecha", que abre el selector de siempre. "Hoy" desaparece después de las 6:00 p. m., porque el vencimiento no puede quedar en el pasado. Debajo se lee la fecha exacta.
- **"¿Ya lo reportaron?"** (`SimilarIncidents`, solo al crear): con el título, busca en el mismo listado, con la visibilidad de cada persona, hasta 3 parecidos. Busca primero con las dos palabras más largas del título (sin palabras vacías como "desde" o "para") y, si no encuentra nada, con la más larga sola: la búsqueda exige todas las palabras, y con el título completo casi nunca habría parecidos.
- **Vista previa** (`IncidentFormPreview`): la misma fila de la lista agrupada, con la prioridad y el plazo que se eligieron.
- **Ayudas fijas en el front** por código, en `domain/reporting.ts`. Un código sin ayuda muestra solo el plazo.

## Alternativas descartadas
- **Solo estilo:** el problema era la estructura, no el color.
- **Por pasos:** son tres campos obligatorios; partirlos alarga el reporte y obliga a ir y volver para corregir.
- **Ayudas en el catálogo:** exige una migración y un campo más en la administración de catálogos, que no existe. Si las prioridades cambiaran a menudo, se movería al catálogo.

## Consecuencias
- Al dejar de escribir el título (400 ms) se hacen una o dos búsquedas, y se cancela la anterior si la persona sigue escribiendo. Usa el límite general de la API.
- Las ayudas de prioridad están en código: una prioridad nueva necesita su texto en el front.

## Dónde está en el código
- Reglas: [`domain/reporting.ts`](../../frontend/src/modules/incidents/domain/reporting.ts) y [`useSimilarIncidents`](../../frontend/src/modules/incidents/application/useSimilarIncidents.ts).
- Pruebas: [`reporting.spec.ts`](../../frontend/src/modules/incidents/application/reporting.spec.ts) y la E2E del formulario en [`incidents.spec.ts`](../../frontend/e2e/incidents.spec.ts).
- Pantalla: [`IncidentForm.vue`](../../frontend/src/modules/incidents/presentation/components/IncidentForm.vue) y [`presentation/form/`](../../frontend/src/modules/incidents/presentation/form/).
