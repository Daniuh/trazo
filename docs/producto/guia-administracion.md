---
title: Guía de administración
description: Para Administrador y Super admin: cuentas, roles, permisos por persona, seguridad de las cuentas, auditoría y sistema.
audience: directivo
area: producto
module: guias
order: 8
---

# Guía de administración

Para el Administrador y el Super admin. Antes de empezar, conviene leer [roles y permisos](roles.md), sobre todo la jerarquía: solo se administra a quien tiene un nivel menor.

Algunas acciones piden el **código de la aplicación de autenticación** aunque la sesión esté abierta. Por eso quien administra necesita tener activa la verificación en dos pasos.

## Personas

En **Usuarios**:

- **Buscar** por nombre o correo, y filtrar por rol o estado.
- **Crear una cuenta:** pulsa **Nueva persona**, indica nombres, apellidos, correo y rol, y confirma con **Crear cuenta**. La persona recibe un correo de bienvenida con un enlace para crear su contraseña; nadie más la conoce.
- **Editar** nombres, apellidos y correo. La lista de personas se ordena por apellido. Si cambia el correo, se avisa a la dirección anterior.
- **Desactivar** una cuenta: sus sesiones se cierran en el acto y no puede volver a entrar. **Activar** la habilita de nuevo. Si tiene incidentes pendientes asignados, la ficha lo advierte para reasignarlos.
- **Ver su actividad:** abre la auditoría filtrada con lo que esa persona hizo.

## Rol y permisos de una persona

En la ficha de la persona:

- **Rol:** elige uno de los roles que están por debajo del tuyo. Al cambiarlo, la persona queda con los permisos exactos del nuevo rol: se quitan los permisos que le habías dado o quitado aparte. Antes de guardar, Trazo te dice cuántos son, y en Auditoría queda cuáles eran.
- **Permisos:** marca o desmarca permisos puntuales. Los marcados que el rol no trae quedan como **concedidos**; los que el rol trae y desmarcas quedan como **quitados**. Solo puedes conceder lo que tú mismo tienes.

La persona ve los cambios al instante: su menú y sus botones se actualizan sin volver a entrar.

## Roles (Super admin)

En **Roles**, el Super admin puede:

- **Crear un rol** con un nombre, un nivel (menor que el propio) y sus permisos. **Copiar permisos de** otro rol ahorra empezar de cero. Los permisos van agrupados por módulo, cada uno con su conteo y la casilla **Todos** para marcarlo o desmarcarlo completo; en el celular los módulos se abren y cierran tocándolos.
- **Editar** los permisos, el nivel o la exigencia de verificación en dos pasos de un rol. Antes de guardar, Trazo indica cuántas personas se verán afectadas.
- **Eliminar** un rol creado, si nadie lo tiene. Los cinco roles base no se eliminan, y el de Super admin no se modifica.

## Seguridad de las cuentas (Super admin)

- **Restablecer la verificación en dos pasos:** para quien perdió el teléfono y sus códigos de recuperación. En el siguiente ingreso la vuelve a configurar.
- **Restablecer el acceso:** anula la contraseña, cierra todas las sesiones y envía un correo para crear una nueva. Sirve cuando se sospecha que alguien conoce la contraseña de la persona.

## Auditoría

- **Auditoría → Administración** muestra quién creó, editó, activó o desactivó cuentas, quién cambió roles y permisos, cada exportación y cada acción del sistema. El Super admin ve todo. El Administrador ve sus propias acciones y las que afectan a personas o roles por debajo de su nivel.
- **Auditoría → Incidentes** está disponible para el Super admin (y para el Supervisor).

Cada registro muestra qué cambió, quién, cuándo y desde dónde. Nada de esto se puede editar ni borrar.

## Sistema (Super admin)

En **Sistema**:

- **Salud:** el estado de cada componente (base de datos, caché, cola de trabajos, proceso que la atiende, tareas programadas, tiempo real y disco), medido en el momento.
- **Acciones:** reiniciar los procesos de la cola, reiniciar el tiempo real, limpiar la caché de datos, ver y reintentar o descartar trabajos fallidos, y activar el **modo mantenimiento**. Cada acción pide el código de la aplicación y queda en la auditoría.
- **Eventos:** las fallas, los problemas de infraestructura y las alertas de seguridad (por ejemplo, un ingreso bloqueado por intentos fallidos), agrupados para no repetir el mismo aviso. Cada evento se puede marcar como revisado o resuelto.

Con el modo mantenimiento activo, las demás personas ven un aviso y no pueden usar Trazo hasta que se desactive. El Super admin sigue trabajando normalmente.

## Para profundizar

- [Roles y permisos](roles.md)
- Operación técnica: [sistema](../operacion/sistema.md)
