---
title: "ADR-0011: Borrado lógico, usuarios desactivados y FK con RESTRICT"
description: "Borrado lógico, usuarios desactivados y FK con RESTRICT"
audience: tecnico
area: decisiones
order: 21
---

# ADR-0011: Borrado lógico, usuarios desactivados y FK con RESTRICT

- **Estado:** Aceptada
- **Fecha:** 2026-10-31

## Contexto
La trazabilidad es prioritaria. Nunca debe perderse quién creó, atendió o modificó algo, y un borrado no debe arrastrar información con valor histórico.

## Alternativas evaluadas
- Borrado físico con CASCADE.
- Borrado físico con SET NULL en las referencias.
- Borrado lógico, con RESTRICT en las relaciones que tienen valor histórico.

## Decisión
- **Incidentes:** se borran de forma lógica (`deleted_at`) y se pueden restaurar.
- **Usuarios:** se desactivan; no se borran.
- **FK con valor histórico:** creador, asignado, actividades y catálogos usan `RESTRICT`.
- **`CASCADE`:** solo en datos que no tienen sentido sin su dueño ni valor histórico, como los tokens de sesión y las asignaciones de rol.
- **Actividades:** son de solo escritura; nunca se editan ni se borran.

## Alternativas descartadas
- **CASCADE:** borrar un usuario eliminaría el historial de lo que hizo.
- **SET NULL:** se perdería quién tenía asignado un incidente o quién hizo un cambio.

## Consecuencias
- La propia base de datos impide cualquier pérdida de trazabilidad.
