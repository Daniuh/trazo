---
title: "ADR-0044: Listado de incidentes filtrado en SQL con la misma regla de visibilidad"
description: "Listado de incidentes filtrado en SQL con la misma regla de visibilidad"
audience: tecnico
area: decisiones
order: 54
---

# ADR-0044: Listado de incidentes filtrado en SQL con la misma regla de visibilidad

- **Estado:** Aceptada
- **Fecha:** 2026-10-31

## Contexto
El listado debe paginar, ordenar, buscar y filtrar sobre cientos de miles de incidentes. Además, cada persona solo puede ver lo que el detalle le permite.

La regla de visibilidad ya existe en el dominio (`IncidentAccess`) y se evalúa sobre un incidente a la vez. El listado no puede cargar todos los incidentes para filtrarlos en PHP.

## Alternativas evaluadas
1. **Filtrar en PHP** con `IncidentAccess`, después de leer de la base de datos.
2. **Escribir la regla otra vez en SQL,** dentro de la consulta del listado.
3. **Extraer la regla a un objeto de dominio** (`VisibilityScope`): el detalle la evalúa en PHP y el listado la traduce a SQL. Una prueba compara ambos resultados para cada persona.

Para la búsqueda:
- `LIKE '%texto%'`.
- El índice FULLTEXT en modo booleano.
- Un motor externo (Meilisearch, Elasticsearch).

## Decisión
- **Alternativa 3 para la visibilidad.**
  - `VisibilityScope` dice si la persona ve todo, lo propio, lo asignado o lo sin asignar, a partir de sus permisos.
  - El listado la convierte en una condición `WHERE`.
  - La prueba "cada persona ve en el listado exactamente lo que el detalle le deja ver" la verifica para todos los usuarios de demostración.
- **Búsqueda con FULLTEXT en modo booleano:**
  - Cada palabra de 3 letras o más es obligatoria y se busca por prefijo.
  - Los operadores del modo booleano se eliminan, para que nadie pueda alterar la consulta.
  - Un texto con forma de código (`INC-123`) se busca exacto.
- **Paginación por número de página,** con el máximo de 100 por página y el total exacto de resultados. Siempre se desempata por id, para que las páginas sean estables.
- **Orden de una lista blanca:** creación, vencimiento, actualización, prioridad, estado y código.

## Alternativas descartadas
- **Filtrar en PHP:** rompe la paginación (cada página tendría un número distinto de filas) y obliga a leer toda la tabla.
- **Escribir la regla dos veces sin conexión entre ambas:** las dos versiones terminarían por diferir, y alguien vería en el listado algo que el detalle le niega, o al revés.
- **`LIKE '%texto%'`:** no puede usar índices y recorre toda la tabla en cada búsqueda.
- **Motor externo:** sería otra tecnología para operar, y la prueba pide no sumar tecnologías. Queda documentado como evolución en [ADR-0014](0014-crecimiento.md).

## Consecuencias
- Cambiar quién ve qué es tocar `VisibilityScope`: el detalle y el listado cambian juntos, y la prueba lo confirma.
- El índice FULLTEXT de InnoDB solo ve las filas confirmadas. Por eso las pruebas de búsqueda guardan de verdad y vacían las tablas al terminar, en lugar de revertir una transacción.
- Los índices se validaron con `EXPLAIN` sobre 100.000 incidentes; los resultados están en [Rendimiento del listado](../base-de-datos/rendimiento.md).
