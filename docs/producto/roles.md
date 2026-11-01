---
title: Roles y permisos
description: Qué puede hacer cada rol, cómo funciona la jerarquía y cómo se dan o quitan permisos a una persona puntual.
audience: directivo
area: producto
module: roles
order: 3
---

# Roles y permisos

Cada persona tiene un rol, y el rol define qué puede hacer. Además, a una persona puntual se le puede dar un permiso adicional o quitarle uno que su rol trae. Así no hace falta inventar un rol nuevo para cada excepción.

## Los cinco roles

| Rol | Para quién | En pocas palabras |
|---|---|---|
| **Solicitante** | Cualquier persona de la organización | Reporta incidentes y sigue los suyos |
| **Agente** | Equipo de soporte | Atiende los incidentes que tiene asignados y toma los que no tienen responsable |
| **Supervisor** | Líder del equipo de soporte | Ve y gestiona todos los incidentes, asigna trabajo, exporta y revisa la auditoría de incidentes |
| **Administrador** | Gestión de personas | Crea cuentas, cambia roles y permisos de quienes están por debajo y revisa la auditoría de administración |
| **Super admin** | Responsable del sistema | Todo lo anterior, más los roles, la seguridad de las cuentas y la salud del sistema |

## Qué puede hacer cada rol

| Acción | Solicitante | Agente | Supervisor | Administrador | Super admin |
|---|:-:|:-:|:-:|:-:|:-:|
| Crear incidentes | ✓ | ✓ | ✓ | ✓ | ✓ |
| Ver los incidentes que reportó | ✓ | ✓ | ✓ | ✓ | ✓ |
| Editar los incidentes que reportó (solo mientras siguen en Abierto) | ✓ | ✓ | ✓ | ✓ | ✓ |
| Ver los incidentes asignados a sí y los que no tienen responsable | | ✓ | ✓ | | ✓ |
| Tomar un incidente sin responsable | | ✓ | ✓ | | ✓ |
| Cambiar el estado de los incidentes que tiene asignados | | ✓ | ✓ | | ✓ |
| Ver, editar y cambiar el estado de cualquier incidente | | | ✓ | | ✓ |
| Asignar o reasignar incidentes a otras personas | | | ✓ | | ✓ |
| Eliminar y restaurar incidentes | | | ✓ | | ✓ |
| Exportar el listado a Excel | | | ✓ | | ✓ |
| Resumen propio ("Mi resumen") | ✓ | ✓ | | ✓ | |
| Resumen de toda la operación ("Resumen general") | | | ✓ | | ✓ |
| Auditoría de incidentes | | | ✓ | | ✓ |
| Auditoría de administración | | | | ✓ | ✓ |
| Crear cuentas, editarlas, activarlas y desactivarlas | | | | ✓ | ✓ |
| Cambiar el rol y los permisos de una persona | | | | ✓ | ✓ |
| Restablecer la verificación en dos pasos o el acceso de alguien | | | | | ✓ |
| Crear y editar roles | | | | | ✓ |
| Ver la salud y los eventos del sistema, y ejecutar sus acciones | | | | | ✓ |

Esta es la configuración inicial. El Super admin puede ajustar qué permisos trae cada rol, y crear roles nuevos, desde la pantalla de Roles.

## La jerarquía

Cada rol tiene un nivel: Solicitante 20, Agente 40, Supervisor 60, Administrador 80 y Super admin 100. La regla es simple: **solo se administra hacia abajo**.

- Un Administrador gestiona a Supervisores, Agentes y Solicitantes, pero no a otros Administradores ni al Super admin.
- El Super admin gestiona a todos, incluidos los Administradores.
- **Nadie puede cambiar su propio rol, sus permisos ni su estado.** Así nadie se da más poder a sí mismo.
- **Siempre queda al menos un Super admin activo.** El sistema no permite desactivar ni quitarle el rol al último.

## Permisos por persona

A una persona se le puede **conceder** un permiso que su rol no trae (por ejemplo, que un agente vea la auditoría de incidentes) o **quitar** uno que sí trae. Lo que puede hacer finalmente es: lo de su rol, más lo concedido, menos lo quitado. Si un permiso aparece concedido y quitado a la vez, gana el quitado.

Nadie puede conceder un permiso que no tiene. Así, un Administrador no puede repartir permisos de seguridad que solo tiene el Super admin.

## Cambios sensibles

Algunas acciones piden **confirmar con el código de la aplicación de autenticación**, aunque la sesión ya esté abierta:

- Conceder o quitar permisos sensibles (los de administrar personas, roles o el sistema).
- Cambiar el rol de alguien cuando el rol anterior o el nuevo incluye esos permisos.
- Restablecer la verificación en dos pasos o el acceso de una cuenta.
- Ejecutar acciones del sistema.

 Si alguien deja la sesión abierta en un equipo, otra persona no puede usarla para darse poder.

Cuando cambian el rol o los permisos de una persona, su menú y sus botones se actualizan al momento, sin que tenga que volver a entrar. Si la desactivan, su sesión se cierra en el acto.

## Para profundizar

- Detalle técnico: [administración de usuarios](../decisiones/0053-administracion-de-usuarios.md) y [edición de roles](../decisiones/0055-edicion-de-roles.md).
- Guía para quien administra: [guía de administración](guia-administracion.md).
