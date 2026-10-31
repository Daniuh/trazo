---
title: "ADR-0014: Estrategia de crecimiento por etapas, sin particionamiento"
description: "Estrategia de crecimiento por etapas, sin particionamiento"
audience: tecnico
area: decisiones
order: 24
---

# ADR-0014: Estrategia de crecimiento por etapas, sin particionamiento

- **Estado:** Aceptada
- **Fecha:** 2026-10-21

## Contexto
El sistema debe crecer en datos y en usuarios sin romperse ni volverse difícil de mantener. La tabla que más crece es la de actividades, porque cada incidente genera varias.

## Alternativas evaluadas
- Optimizar solo cuando aparezcan problemas.
- Particionar las tablas grandes desde el inicio.
- Una estrategia por etapas, con lo esencial desde el primer día.

## Decisión
Una estrategia por etapas.

**Desde el primer día:**
- Ids BIGINT.
- Paginación obligatoria, con un máximo de registros por página impuesto en el backend.
- Índices validados con `EXPLAIN` sobre más de 100.000 registros.
- Caché del dashboard.
- Registro de consultas lentas.
- API sin estado, que escala de forma horizontal.

**Documentado para etapas siguientes:** réplica de lectura, tabla resumen del dashboard, archivado de registros antiguos y motor de búsqueda externo.

**Migraciones:** solo aditivas, con el patrón *expand/contract*.

## Alternativas descartadas
- **Optimizar sobre la marcha:** los problemas de rendimiento aparecen cuando ya hay usuarios afectados.
- **Particionamiento:** en MySQL, las tablas particionadas no admiten claves foráneas, y eso rompería la integridad definida en el ADR-0009. El archivado cumple el mismo objetivo y conserva las FK.

## Consecuencias
- El costo inicial es bajo, y cada etapa siguiente ya tiene un camino definido.
