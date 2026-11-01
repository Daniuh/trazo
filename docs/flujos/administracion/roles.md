---
title: Crear, editar y eliminar roles
description: Roles con su nivel, sus permisos y su política de MFA; cómo un cambio llega a todos sus miembros y qué roles no se tocan.
audience: tecnico
area: flujos
module: users
order: 5
---

# Crear, editar y eliminar roles

Con `roles.manage` (Super admin) se crean roles nuevos, por ejemplo "soporte_n2", y se editan los existentes: nombre, nivel, permisos y política de MFA. Las reglas consultan permisos, nunca nombres de rol, así que un rol nuevo funciona en todo Trazo sin tocar código.

- El rol Super admin no se edita ni se elimina: siempre tiene todos los permisos.
- Los 5 roles base (`is_system`) se pueden editar pero no eliminar.
- Un rol con personas no se elimina: primero se les cambia el rol.

```flujo
carriles: [Super admin, Navegador, API, Base de datos, Miembros del rol]
pasos:
  - id: edita
    carril: Super admin
    tipo: inicio
    texto: Crea o edita un rol
    detalle: 'En `/administracion/roles`. Al crear se puede partir de "Copiar permisos de" otro rol. Antes de guardar, la confirmación dice cuántas personas se verán afectadas.'
    va: envia
  - id: envia
    carril: Navegador
    texto: Envía POST /roles o PATCH /roles/{code}
    va: puede
  - id: puede
    carril: API
    tipo: decision
    texto: ¿Rol por debajo de su nivel y no bloqueado?
    detalle: 'El nivel nuevo como máximo el propio − 1 (99 para la cima) y único. El código, en minúsculas, números y guion bajo, empezando por letra.'
    si: concede
    no: e422
  - id: e422
    carril: API
    tipo: error
    texto: Responde 403, 409 o 422
  - id: concede
    carril: API
    tipo: decision
    texto: ¿Solo permisos que él tiene?
    si: sensible
    no: e422p
  - id: e422p
    carril: API
    tipo: error
    texto: Responde 422
  - id: sensible
    carril: API
    tipo: decision
    texto: ¿Cambia el nivel, algo sensible o relaja el MFA?
    detalle: Si sí, pide el código de su aplicación (reautenticación), igual que al cambiar el rol de una persona.
    si: codigo
    no: guarda
  - id: codigo
    carril: Super admin
    texto: Confirma con su código
    va: guarda
  - id: guarda
    carril: Base de datos
    texto: Guarda el rol y poda excepciones sobrantes
    detalle: 'Si cambiaron los permisos, se quitan las excepciones de sus miembros que el rol ya cubre o que ya no tienen sentido. Registro `role_created` o `role_updated` en `admin_actions` con lo que cambió.'
    va: miembros
  - id: miembros
    carril: Miembros del rol
    tipo: fin
    texto: Sus pantallas se ajustan sin recargar
    detalle: '`session.changed` a cada miembro. No se envía correo masivo.'
  - id: elimina
    carril: Super admin
    tipo: inicio
    texto: Elimina un rol creado
    va: borrable
  - id: borrable
    carril: API
    tipo: decision
    texto: ¿No es base y nadie lo tiene?
    si: borra
    no: e409
  - id: e409
    carril: API
    tipo: error
    texto: Responde 409 ROLE_SYSTEM o ROLE_IN_USE
  - id: borra
    carril: Base de datos
    tipo: fin
    texto: Lo borra y registra código y nombre
    detalle: 'Los registros anteriores que apuntaban al rol quedan con `target_role_id` vacío (SET NULL), pero su detalle conserva el código y el nombre.'
```

Eliminar (pasos 9 a 11) es un camino aparte, con su propio inicio.

## Qué ve la persona en cada error

| Código | HTTP | Cuándo | Mensaje en pantalla |
|---|---|---|---|
| `ROLE_OUTRANKED` | 403 | El rol está en su nivel o por encima | Este rol está en tu nivel o por encima, así que no lo puedes cambiar. |
| `ROLE_LOCKED` | 409 | Se intenta editar o eliminar el Super admin | El rol Super admin tiene siempre todos los permisos, así que no se edita ni se elimina. |
| `ROLE_SYSTEM` | 409 | Se intenta eliminar un rol base | Este es uno de los roles base de Trazo, así que no se puede eliminar. Sí puedes editarlo. |
| `ROLE_IN_USE` | 409 | El rol tiene personas | Hay personas con este rol. Cámbiales el rol antes de eliminarlo. |
| `ROLE_LEVEL_NOT_ALLOWED` | 422 | El nivel no está por debajo del suyo | Elige un nivel por debajo del tuyo: como máximo {max}. |
| `ROLE_CODE_FORMAT` | 422 | El código no cumple el formato | Usa solo letras minúsculas sin tildes, números y guion bajo, empezando por una letra (por ejemplo, soporte_n2). |
| `PERMISSION_NOT_GRANTABLE` | 422 | Incluye permisos que él no tiene | Solo puedes conceder permisos que tú también tienes. Revisa los permisos marcados y vuelve a intentarlo. |

## Dónde está en el código

- [`CreateRole`](../../../backend/app/Modules/Users/Application/CreateRole.php), [`UpdateRole`](../../../backend/app/Modules/Users/Application/UpdateRole.php) y [`DeleteRole`](../../../backend/app/Modules/Users/Application/DeleteRole.php).
- [`RoleAccess`](../../../backend/app/Modules/Users/Domain/RoleAccess.php) y [`RoleGate`](../../../backend/app/Modules/Users/Application/RoleGate.php).
- [`DatabaseRoleRepository`](../../../backend/app/Modules/Users/Infrastructure/DatabaseRoleRepository.php): miembros y poda de excepciones.
- [`RoleFormPage`](../../../frontend/src/modules/admin/presentation/RoleFormPage.vue), [`RoleFields`](../../../frontend/src/modules/admin/presentation/components/RoleFields.vue) y [`useRoles`](../../../frontend/src/modules/admin/application/useRoles.ts).
- Explicación: [Módulo Users](../../backend/users.md).
