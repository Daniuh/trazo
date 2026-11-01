---
title: "ADR-0047: Métricas del dashboard con la visibilidad del listado y caché invalidada por cambios"
description: "Métricas del dashboard con la visibilidad del listado y caché invalidada por cambios"
audience: tecnico
area: decisiones
tags: [producto]
order: 57
---

# ADR-0047: Métricas del dashboard con la visibilidad del listado y caché invalidada por cambios

- **Estado:** Aceptada
- **Fecha:** 2026-10-31

## Contexto
La prueba pide total, incidentes por estado, vencidos y por prioridad, y que el sistema rinda con mucho volumen. [ADR-0014](0014-crecimiento.md) ya decidió cachear el dashboard desde el día uno.

Quedaban abiertas cuatro preguntas:

- Qué cuenta cada persona.
- Cómo se mantiene la caché al día.
- Cómo se cuentan los días de la tendencia.
- Quién decide a qué listado lleva cada cifra.

## Alternativas evaluadas
Alcance de las cifras:
1. Según el permiso del dashboard: `view.own` cuenta solo lo creado por la persona y `view.any` cuenta todo.
2. **La misma regla de visibilidad del listado** (`VisibilityScope`), compartida en SQL (`VisibilitySql`).

Frescura:
1. Solo tiempo de vida (por ejemplo, 5 minutos).
2. Borrar las claves afectadas en cada cambio.
3. **Número de generación en todas las claves**, que sube con cada cambio, más un tiempo de vida corto.

Días de la tendencia:
1. Agrupar por día en UTC.
2. Convertir la zona en MySQL (`CONVERT_TZ`).
3. **Agrupar por hora UTC en MySQL y armar los días locales en la aplicación.**

Enlaces de cada cifra:
1. El front arma el filtro.
2. **El back devuelve el filtro del listado junto con cada cifra.**

## Decisión
- Alcance 2: un agente cuenta lo propio, lo asignado y lo sin asignar, igual que en su listado.
- Frescura 3: el evento `IncidentChanged` sale después de confirmar cada escritura y sube la generación. El tiempo de vida es de 60 segundos para lo que cambia solo con la hora (los vencimientos).
- Días 3, con la zona del negocio en la configuración (`America/Bogota`).
- Enlaces 2: cada cifra trae su `filter` (estado, prioridad, asignado, vencidos o rango de vencimiento).
- Fuentes compartidas: un solo conteo por estado y prioridad alimenta cuatro widgets.
- Índices de cobertura que incluyen `deleted_at` y las columnas de visibilidad, validados con `EXPLAIN` sobre 100.000 incidentes.

## Alternativas descartadas
- **Alcance según el permiso del dashboard:** un agente vería en el dashboard cifras distintas a las de su listado, y nadie sabría cuál creer.
- **Solo tiempo de vida:** quien crea un incidente no lo vería contado hasta que expire la caché.
- **Borrar las claves afectadas:** hay una clave por alcance y por fuente. Saber cuáles toca un cambio obliga a buscar claves (`KEYS`/`SCAN`), lento y frágil en Redis.
- **Días en UTC:** en Colombia, lo creado después de las 7 p. m. aparecería al día siguiente.
- **`CONVERT_TZ`:** exige cargar las tablas de zonas horarias en MySQL y no usa los índices de fecha.
- **El front arma el filtro:** tendría que saber qué estados son "pendientes", una regla de negocio que vive en el back.

## Consecuencias
- Una prueba compara cada cifra con el total del listado filtrado, para supervisor, agente y solicitante.
- Con muchos cambios seguidos, la caché casi no se aprovecha: cada cambio la invalida completa. Con el volumen esperado no pesa; si llegara a pesar, el siguiente paso es la tabla resumen documentada en [ADR-0014](0014-crecimiento.md).
- En la fase de tiempo real, el mismo evento `IncidentChanged` avisará a las pantallas abiertas.
