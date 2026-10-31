---
title: "ADR-0013: Búsqueda por texto con índice FULLTEXT"
description: "Búsqueda por texto con índice FULLTEXT"
audience: tecnico
area: decisiones
order: 23
---

# ADR-0013: Búsqueda por texto con índice FULLTEXT

- **Estado:** Aceptada
- **Fecha:** 2026-10-21

## Contexto
El listado debe permitir búsqueda por texto, que es obligatoria, y sostener grandes volúmenes de datos.

## Alternativas evaluadas
- `LIKE '%texto%'`.
- Índice FULLTEXT de MySQL.
- Motor externo (Meilisearch, Elasticsearch).

## Decisión
Índice FULLTEXT sobre `title` y `description`, en modo booleano para permitir la búsqueda por prefijo. Antes de consultar se limpian los operadores especiales que escriba el usuario.

## Alternativas descartadas
- **LIKE:** recorre la tabla completa y se degrada a medida que crece el volumen.
- **Motor externo:** es otra pieza de infraestructura que hay que operar y mantener sincronizada. Queda propuesto para una etapa de mayor volumen.

## Consecuencias
- Las palabras de menos de 3 letras no se indexan. Es una limitación conocida y queda documentada.
