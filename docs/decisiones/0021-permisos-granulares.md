---
title: "ADR-0021: Roles construidos sobre permisos granulares"
description: "Roles construidos sobre permisos granulares"
audience: tecnico
area: decisiones
order: 31
---

# ADR-0021: Roles construidos sobre permisos granulares

- **Estado:** Aceptada
- **Fecha:** 2026-10-21

## Contexto
El sistema tiene cuatro roles: Solicitante, Agente, Supervisor y Super admin. Se deben poder ajustar los roles existentes o crear otros nuevos sin tocar código.

## Alternativas evaluadas
- Comprobar el nombre del rol en el código.
- Permisos granulares agrupados en roles.

## Decisión
- **Formato de los permisos:** `módulo.acción[.alcance]`, donde el alcance puede ser `own`, `assigned`, `unassigned` o `any`. Por ejemplo: `incidents.view.assigned` o `system.actions.execute`.
- **Policies:** consultan permisos, nunca nombres de rol.
- **Reglas de protección:**
  - Siempre existe al menos un super admin activo.
  - Nadie puede cambiar su propio rol.

## Alternativas descartadas
- **Comprobar roles:** cada cambio en lo que puede hacer un rol obliga a modificar código en varios lugares.

## Consecuencias
- Crear un rol o mover un permiso es un cambio de datos.
- La matriz completa de permisos se cubre con pruebas automáticas.
