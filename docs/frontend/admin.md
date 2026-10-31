---
title: Módulo de administración (frontend)
description: Listado de usuarios, crear una cuenta y ficha de cada persona con su rol, permisos y acceso.
audience: tecnico
area: frontend
module: admin
order: 6
---

# Módulo de administración (frontend)

Pantallas para administrar a las personas: el listado, crear una cuenta y la ficha de cada una. **Las reglas viven en la API** ([Módulo Users](../backend/users.md)): qué botones aparecen sale del campo `actions` de cada persona; qué roles y permisos se pueden dar sale de `assignable` y `grantable`. El front no calcula la jerarquía ni decide qué es sensible.

El menú muestra "Usuarios" solo con `users.view`, y "Nueva persona" solo con `users.create`.

## Pantallas

| Ruta | Pantalla |
|---|---|
| `/administracion/usuarios` | Listado con búsqueda y filtros en la URL |
| `/administracion/usuarios/nuevo` | Crear una cuenta |
| `/administracion/usuarios/:id` | Ficha de la persona |

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

- **La cuenta propia:** un aviso explica que solo puede corregir sus datos. El resto lo cambia otra persona.
- **Desactivar** pide confirmación. Si la persona tiene incidentes pendientes asignados, la confirmación lo dice: no se reasignan solos.
- **En el editor de permisos**, un permiso que quien administra no tiene, y que la persona tampoco tiene, queda deshabilitado. El ícono de información explica por qué. Quitarlo sí se puede.

## Reautenticación

`useReauth` intenta el cambio sin código. Si la API responde `REAUTHENTICATION_REQUIRED`, abre `AppCodeDialog` y lo reintenta con el código. Si la persona cierra el diálogo, el cambio no se hizo y el diálogo de origen (por ejemplo, el de cambiar rol) sigue abierto.

Restablecer el MFA o el acceso siempre lo pide, así que el diálogo del código se abre de una vez y sirve también como confirmación.

## Capas

| Capa | Pieza | Qué hace |
|---|---|---|
| domain | `user.ts` | Tipos: persona, ficha, acciones permitidas, opciones de rol y de permiso |
| domain | `listing.ts`, `permissions.ts` | Criterios del listado; agrupar permisos por módulo y marcar lo que difiere del rol (lógica pura) |
| infrastructure | `usersApi.ts` | Llamadas a `/users`, `/roles` y `/permissions`, y traducción de la respuesta |
| application | `useUserList`, `listQuery` | Listado ligado a la URL |
| application | `useUserDetail`, `useUserCreate`, `profileRules` | Ficha y sus cambios; crear con idempotencia; revisión visual de nombre y correo |
| application | `useAccessCatalogStore` | Roles y permisos para los formularios (Pinia), una vez por persona con sesión |
| presentation | `UserListPage`, `list/UserToolbar`, `list/UserFilterFields`, `list/UserTable`, `list/UserCards` | Listado |
| presentation | `UserCreatePage`, `UserDetailPage`, `components/*` | Crear una cuenta, ficha, tarjetas y diálogos |
| presentation | `useReauth`, `userTexts` | Diálogo del código cuando la API lo pide; textos y tonos (qué hace cada rol, estado, último ingreso) |
| — | `errors.ts`, `routes.ts`, `navigation.ts` | Mensajes, rutas y entrada del menú |

## Dónde está en el código

- [`frontend/src/modules/admin/`](../../frontend/src/modules/admin/)
- Componentes compartidos: [`AppDataTable`](../../frontend/src/shared/ui/AppDataTable.vue), [`AppFilterSheet`](../../frontend/src/shared/ui/AppFilterSheet.vue), [`AppRadioList`](../../frontend/src/shared/ui/form/AppRadioList.vue), [`AppFormDialog`](../../frontend/src/shared/ui/feedback/AppFormDialog.vue) y [`AppCodeDialog`](../../frontend/src/shared/ui/feedback/AppCodeDialog.vue)
- Listado ligado a la URL: [`shared/listing/useRouteList.ts`](../../frontend/src/shared/listing/useRouteList.ts)
- Pruebas: `*.spec.ts` del módulo y [`e2e/users.spec.ts`](../../frontend/e2e/users.spec.ts)
- Decisiones: [ADR-0053](../decisiones/0053-administracion-de-usuarios.md)
