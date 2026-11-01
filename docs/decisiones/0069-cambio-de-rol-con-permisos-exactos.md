---
title: "ADR-0069: Al cambiar de rol, la persona queda con los permisos exactos del rol nuevo"
description: "El cambio de rol borra todas las excepciones de permisos de la persona y el registro de auditoría guarda cuáles eran"
audience: tecnico
area: decisiones
tags: [seguridad]
order: 79
---

# ADR-0069: Al cambiar de rol, la persona queda con los permisos exactos del rol nuevo

- **Estado:** Aceptada. Reemplaza en parte al [ADR-0053](0053-administracion-de-usuarios.md) en lo que pasa con las excepciones al cambiar de rol.
- **Fecha:** 2026-10-31

## Contexto
Cada persona tiene los permisos de su rol más sus excepciones: permisos concedidos aparte y permisos del rol que se le quitaron ([ADR-0053](0053-administracion-de-usuarios.md)).

Hasta ahora, al cambiar el rol solo se quitaban las excepciones que el rol nuevo volvía innecesarias. Un concedido que el rol nuevo no traía se conservaba. Por ejemplo, un agente con "Ver la auditoría de administración" concedida aparte la seguía teniendo como supervisor. Quien cambiaba el rol no lo veía en el diálogo, y la persona podía terminar con permisos que nadie eligió para su nuevo puesto.

## Alternativas evaluadas
- Borrar todas las excepciones al cambiar de rol: la persona queda con los permisos exactos del rol nuevo.
- Mantener la poda: quitar solo las excepciones que el rol nuevo vuelve innecesarias.
- Preguntar en cada cambio de rol si se conservan o se borran las excepciones.

## Decisión
- **Al cambiar de rol se borran todas las excepciones** (concedidos y quitados). La regla vive en el caso de uso `ChangeUserRole`, en el back.
- **Queda registro de lo que se borró:** el registro de auditoría del cambio de rol guarda, en `cleared`, los permisos concedidos y quitados que tenía la persona. La pantalla de Auditoría los muestra como "Permisos adicionales que se quitaron" y "Permisos del rol que se devolvieron".
- **Aviso antes de guardar:** si la persona tiene cambios personalizados, el diálogo de cambio de rol avisa cuántos se van a quitar.
- Si después hace falta un permiso extra, se concede de nuevo desde la tarjeta de permisos, de forma explícita.

## Alternativas descartadas
- **Mantener la poda:** deja permisos arrastrados del rol anterior que nadie revisó para el puesto nuevo. Va contra el principio de mínimo privilegio y es difícil de auditar.
- **Preguntar en cada cambio:** suma una decisión más a una acción que debe ser simple. La respuesta segura sería casi siempre "borrar", así que la pregunta aporta poco y abre la puerta a conservar permisos por inercia.

## Consecuencias
- Cambiar de rol es predecible: la persona tiene exactamente lo que dice su rol.
- Si a alguien se le cambia el rol y necesitaba un permiso extra, hay que concedérselo otra vez. El aviso del diálogo y el registro de auditoría dicen cuáles tenía.
- Editar los permisos de un rol no cambia: sigue quitando solo las excepciones que el cambio vuelve innecesarias en sus personas ([ADR-0055](0055-edicion-de-roles.md)), porque ahí no cambia el puesto de nadie.

## Dónde está en el código
- [`backend/app/Modules/Users/Application/ChangeUserRole.php`](../../backend/app/Modules/Users/Application/ChangeUserRole.php): `clearOverrides`.
- [`backend/tests/Feature/Users/UserAccessTest.php`](../../backend/tests/Feature/Users/UserAccessTest.php): "al cambiar de rol la persona queda con los permisos exactos del rol nuevo".
- [`frontend/src/modules/admin/presentation/components/RoleDialog.vue`](../../frontend/src/modules/admin/presentation/components/RoleDialog.vue): aviso antes de guardar.
- [`frontend/src/modules/audit/presentation/auditTexts.ts`](../../frontend/src/modules/audit/presentation/auditTexts.ts): textos de `cleared`.
