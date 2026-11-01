---
title: Módulo de administración (frontend)
description: Usuarios (listado, crear una cuenta y ficha con su rol, permisos y acceso) y roles (listado, crear, editar y eliminar).
audience: tecnico
area: frontend
module: admin
order: 6
---

# Módulo de administración (frontend)

Pantallas para administrar a las personas (el listado, crear una cuenta y la ficha de cada una) y los roles. **Las reglas viven en la API** ([Módulo Users](../backend/users.md)): qué botones aparecen sale del campo `actions` de cada persona o rol; qué roles y permisos se pueden dar sale de `assignable` y `grantable`. El front no calcula la jerarquía ni decide qué es sensible.

El menú muestra "Usuarios" solo con `users.view` y "Roles" solo con `roles.manage`. "Nueva persona" aparece con `users.create` y "Nuevo rol" con `roles.manage`.

## Pantallas

| Ruta | Pantalla |
|---|---|
| `/administracion/usuarios` | Listado con búsqueda y filtros en la URL |
| `/administracion/usuarios/nuevo` | Crear una cuenta |
| `/administracion/usuarios/:id` | Ficha de la persona |
| `/administracion/roles` | Listado de roles |
| `/administracion/roles/nuevo` | Crear un rol |
| `/administracion/roles/:code` | Ficha del rol (ver y editar) |

### Listado

| Vista | Forma |
|---|---|
| Portátil y monitor | Barra con búsqueda y tres selectores (rol, estado y verificación en dos pasos); tabla (`AppDataTable`) que se ordena por nombre, correo o último ingreso |
| Tablet y celular | Búsqueda, "Filtros (n)" que abre el panel inferior (`AppFilterSheet`) y "Ordenar por"; una tarjeta por persona |

- **Criterios en la URL** (`role`, `status`, `mfa`, `search`, `sort`, `page`, `per_page`), con la misma mecánica del listado de incidentes (`useRouteList`).
- **Búsqueda al dejar de escribir**, por nombre o correo.
- **Filtros de una sola opción** (estado y MFA): se quitan eligiendo "Todas".

### Crear una cuenta

- **Campos:** nombre, correo y rol. El rol se elige con `AppRadioList`, solo entre los roles que quien crea puede dar, cada uno con una línea que explica qué hace.
- **Contraseña:** no se define ninguna. Un aviso a la vista explica que a la persona le llega un correo para crearla.
- **Si el rol trae permisos de administración**, la API pide el código de quien crea y se abre el diálogo del código.
- **Doble envío:** la creación lleva `Idempotency-Key`, así un doble clic no crea dos cuentas.
- **Al terminar**, va a la ficha nueva con el aviso "Cuenta creada…".

### Ficha

Portátil y monitor: dos columnas iguales, con Datos, Rol y Acceso a la izquierda y Permisos a la derecha. Celular y tablet: una columna.

| Tarjeta | Qué muestra | Acciones |
|---|---|---|
| Datos | Nombre y correo | "Editar" abre `ProfileDialog`. Si el correo cambió, el aviso dice que se avisó a la dirección anterior |
| Rol | Rol actual y qué hace | "Cambiar rol" abre `RoleDialog` (`AppRadioList`) |
| Acceso | Estado, verificación en dos pasos, último ingreso, fecha de creación e incidentes pendientes asignados (con "Ver sus incidentes" si quien administra puede ver todos) | Activar o desactivar, restablecer la verificación en dos pasos y restablecer el acceso |
| Permisos | Resumen ("Permisos de Agente con 1 cambio") y los permisos agrupados por módulo; los que difieren del rol llevan "Adicional" o "Quitado del rol" | "Editar permisos": una casilla por permiso, "Volver a los del rol", Cancelar y Guardar |

- **"Ver su actividad"** (bajo el nombre, con permiso de auditoría): lleva a la auditoría filtrada por esa persona, en la pestaña de administración o, si solo se tiene la de incidentes, en esa ([Módulo de auditoría](audit.md)).
- **La cuenta propia:** un aviso explica que solo puede corregir sus datos. El resto lo cambia otra persona.
- **Desactivar** pide confirmación. Si la persona tiene incidentes pendientes asignados, la confirmación lo dice: no se reasignan solos.
- **En el editor de permisos**, un permiso que quien administra no tiene, y que la persona tampoco tiene, queda deshabilitado. El ícono de información explica por qué. Quitarlo sí se puede.

### Roles

- **Listado,** de mayor a menor nivel: nombre (con "Base" en los cinco roles de sistema), código, nivel, personas, cuántos permisos trae y su política de MFA ("Obligatoria · 1 de gracia" u "Opcional").
  - Portátil y monitor: tabla (`AppDataTable`).
  - Tablet y celular: tarjetas.
- **Crear y editar** comparten la pantalla (`RoleFormPage`).
  - Portátil y monitor: Datos y Verificación en dos pasos a la izquierda, Permisos a la derecha. Celular y tablet: una columna.
  - **Datos** (`RoleFields`): código (solo al crear; después no cambia), nombre y nivel, con la regla de cada uno en el ícono de información. El nivel usa el teclado numérico en el celular.
  - **Verificación en dos pasos:** casilla "Obligatoria para este rol" y, si lo es, los ingresos en que se puede posponer.
  - **Permisos:** `PermissionChecklist` con casillas. Al crear, "Copiar permisos de" otro rol llena la lista para no empezar desde cero.
- **Guardar un rol que tiene personas** pide confirmación indicando a cuántas afecta. Si la API pide el código, se abre el diálogo del código.
- **"Eliminar rol"** solo aparece si la API lo permite (un rol creado que nadie tiene) y pide confirmación.
- **Si el rol no se puede editar** (el Super admin, o uno en el nivel de quien administra o por encima), se muestra sin campos y con un aviso que dice por qué.
- **Formularios de personas al día:** después de cada cambio de un rol, el catálogo de los formularios de personas se invalida (`useAccessCatalogStore.invalidate`) y se vuelve a leer en la siguiente pantalla.

## Reautenticación

`useReauth` (en `shared/ui/feedback`, porque también lo usa la [pantalla de sistema](system.md)) intenta el cambio sin código. Si la API responde `REAUTHENTICATION_REQUIRED`, abre `AppCodeDialog` y lo reintenta con el código. Si la persona cierra el diálogo, el cambio no se hizo y el diálogo de origen (por ejemplo, el de cambiar rol) sigue abierto.

Restablecer el MFA o el acceso siempre lo pide, así que el diálogo del código se abre de una vez y sirve también como confirmación.

## Capas

| Capa | Pieza | Qué hace |
|---|---|---|
| domain | `user.ts`, `role.ts` | Tipos: persona, ficha, acciones permitidas, opciones de rol y de permiso; rol, su borrador y si cambió |
| domain | `listing.ts`, `permissions.ts` | Criterios del listado; agrupar permisos por módulo y marcar lo que difiere del rol (lógica pura) |
| infrastructure | `usersApi.ts`, `rolesApi.ts` | Llamadas a `/users`, `/roles` y `/permissions`, y traducción de la respuesta. El código de quien administra viaja como `mfa_code` |
| application | `useUserList`, `listQuery` | Listado ligado a la URL |
| application | `useUserDetail`, `useUserCreate`, `profileRules` | Ficha y sus cambios; crear con idempotencia; revisión visual de nombre y correo |
| application | `useRoles` (`useRoleList`, `useRoleForm`) | Listado de roles y formulario (crear, editar y eliminar) |
| application | `useAccessCatalogStore` | Roles y permisos para los formularios (Pinia), una vez por persona con sesión; se invalida al cambiar un rol |
| presentation | `UserListPage`, `list/UserToolbar`, `list/UserFilterFields`, `list/UserTable`, `list/UserCards` | Listado de usuarios |
| presentation | `UserCreatePage`, `UserDetailPage`, `components/*` | Crear una cuenta, ficha, tarjetas y diálogos |
| presentation | `RoleListPage`, `RoleFormPage`, `components/RoleFields`, `components/PermissionChecklist` | Roles; la lista de permisos la comparten la ficha de persona y la de rol |
| presentation | `userTexts` | Textos y tonos (qué hace cada rol, estado, último ingreso, política de MFA). El diálogo del código usa `useReauth` de `shared/ui/feedback` |
| — | `errors.ts`, `routes.ts`, `navigation.ts` | Mensajes, rutas y entradas del menú |

## Dónde está en el código

- [`frontend/src/modules/admin/`](../../frontend/src/modules/admin/)
- Componentes compartidos: [`AppDataTable`](../../frontend/src/shared/ui/AppDataTable.vue), [`AppFilterSheet`](../../frontend/src/shared/ui/AppFilterSheet.vue), [`AppRadioList`](../../frontend/src/shared/ui/form/AppRadioList.vue), [`AppFormDialog`](../../frontend/src/shared/ui/feedback/AppFormDialog.vue) y [`AppCodeDialog`](../../frontend/src/shared/ui/feedback/AppCodeDialog.vue)
- Listado ligado a la URL: [`shared/listing/useRouteList.ts`](../../frontend/src/shared/listing/useRouteList.ts)
- Pruebas: `*.spec.ts` del módulo, [`e2e/users.spec.ts`](../../frontend/e2e/users.spec.ts) y [`e2e/roles.spec.ts`](../../frontend/e2e/roles.spec.ts)
- Decisiones: [ADR-0053](../decisiones/0053-administracion-de-usuarios.md) y [ADR-0055](../decisiones/0055-edicion-de-roles.md)
