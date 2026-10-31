---
title: "ADR-0009: Sin relaciones polimórficas: toda referencia es una FK"
description: "Sin relaciones polimórficas: toda referencia es una FK"
audience: tecnico
area: decisiones
order: 19
---

# ADR-0009: Sin relaciones polimórficas: toda referencia es una FK

- **Estado:** Aceptada
- **Fecha:** 2026-10-21

## Contexto
Toda referencia entre tablas, incluidas las que apuntan a usuarios, debe estar respaldada por una clave foránea real. Así ningún registro queda imposible de mapear en una migración o integración.

## Alternativas evaluadas
- Paquetes estándar del ecosistema: auditoría genérica polimórfica y spatie/laravel-permission, que usa las columnas `model_type` y `model_id`.
- Tablas propias con claves foráneas.

## Decisión
- **Auditoría:** se guarda en `incident_activities`, con FKs a `incidents`, `users` y `activity_types`. Si otra entidad la necesita, tendrá su propia tabla de actividad.
- **RBAC propio:** las tablas `roles`, `permissions`, `role_permission` y `user_role`, todas con FK.
- **Sanctum:** se conserva, pero su tabla de tokens recibe una FK de `tokenable_id` hacia `users`, con CASCADE.

## Alternativas descartadas
- **Relaciones polimórficas:** la columna del id no puede tener FK, porque apunta a tablas distintas. La integridad queda en manos del código.

## Consecuencias
- La base de datos garantiza la integridad de todas las relaciones.
- Hay unas pocas tablas más que mantener, a cambio de eliminar una dependencia externa.
