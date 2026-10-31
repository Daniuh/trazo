---
title: "ADR-0008: Estado y prioridad como tablas catálogo"
description: "Estado y prioridad como tablas catálogo"
audience: tecnico
area: decisiones
order: 18
---

# ADR-0008: Estado y prioridad como tablas catálogo

- **Estado:** Aceptada
- **Fecha:** 2026-10-21

## Contexto
Los estados y las prioridades deben poder ajustarse sin migraciones que rompan dependencias. La integridad la debe garantizar la base de datos, sin perder rendimiento.

## Alternativas evaluadas
- ENUM nativo de MySQL.
- Columna simple con restricción CHECK y un enum en el código.
- Tablas catálogo con claves foráneas.

## Decisión
Tablas catálogo:
- `priorities`: con `code`, `level` y el tiempo de resolución por defecto.
- `statuses`: con las banderas `is_initial`, `is_pending` e `is_final`.
- `status_transitions`: el flujo permitido entre estados, guardado como datos.

Los catálogos se referencian siempre por su `code` y nunca se borran: se desactivan. Los colores no se guardan en la base de datos, sino en los tokens de diseño.

## Alternativas descartadas
- **ENUM:** agregar un valor exige un `ALTER TABLE`, que bloquea la tabla cuando es grande.
- **Columna con CHECK:** cada valor nuevo exige una migración para cambiar la restricción, y el flujo de estados quedaría en el código.

## Consecuencias
- Agregar un estado o cambiar el flujo se resuelve insertando filas.
- El costo de los JOIN es mínimo: son tablas de pocas filas consultadas por clave primaria.
