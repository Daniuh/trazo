---
title: Problemas frecuentes
description: Lo que una persona reporta, por qué pasa, qué hacer y quién lo resuelve, sin tecnicismos.
audience: soporte
area: soporte
order: 3
---

# Problemas frecuentes

Respuestas para atender a una persona sin entrar en detalles técnicos. Cada problema dice por qué pasa, qué hacer, quién lo resuelve y qué flujo o error lo explica. Si el caso muestra un mensaje de error, el [catálogo de errores](catalogo-de-errores.md) explica cada uno.

## Ingreso y cuenta

### No puedo entrar y la contraseña está bien

- **Por qué pasa:** el mensaje de ingreso es el mismo para un correo que no existe, una contraseña incorrecta o una cuenta desactivada, para no revelar qué cuentas existen.
- **Qué hacer:** revisar en **Usuarios** que la cuenta exista con ese correo y que esté activa.
- **Lo resuelve:** Administrador.
- **Para entenderlo:** [Ingreso con verificación en dos pasos](../flujos/acceso/ingreso.md) · error `INVALID_CREDENTIALS`.

### Dice que el ingreso está pausado

- **Por qué pasa:** hubo 5 o más intentos fallidos seguidos con ese correo. La espera crece con cada tanda: 1 minuto, 5, 15 y luego 1 hora.
- **Qué hacer:** esperar el tiempo que indica el mensaje. Si la persona no hizo esos intentos, alguien puede estar probando su cuenta: revisar los eventos de seguridad en **Sistema → Eventos** y, si hace falta, restablecer el acceso.
- **Lo resuelve:** la persona; el Super admin si no fue ella.
- **Para entenderlo:** [Ingreso con verificación en dos pasos](../flujos/acceso/ingreso.md) · error `ACCOUNT_LOCKED`.

### El código de la aplicación siempre sale incorrecto

- **Por qué pasa:** casi siempre es la hora del teléfono, porque los códigos dependen de la hora exacta. También pasa si se escribe un código que ya se usó.
- **Qué hacer:** activar la fecha y hora automáticas en los ajustes del teléfono. Si el código ya se usó, esperar el siguiente (cambia cada 30 segundos).
- **Lo resuelve:** la persona.
- **Para entenderlo:** [Ingreso con verificación en dos pasos](../flujos/acceso/ingreso.md) · errores `MFA_CODE_INVALID` y `MFA_CODE_ALREADY_USED`.

### Perdí el teléfono (o cambié de teléfono)

- **Por qué pasa:** la verificación en dos pasos vive en la aplicación del teléfono.
- **Qué hacer:** si la persona guardó sus **códigos de recuperación**, entra con uno y vuelve a configurar la verificación desde **Mi cuenta**. Si no los tiene, el Super admin restablece su verificación en dos pasos desde la ficha de la persona; en el siguiente ingreso la configura de nuevo.
- **Lo resuelve:** la persona con sus códigos; si no, el Super admin.
- **Para entenderlo:** [Códigos de recuperación](../flujos/acceso/codigos-de-recuperacion.md) y [Restablecer MFA o acceso](../flujos/administracion/restablecer-acceso.md).

### No me llega el correo con el código de recuperación

- **Por qué pasa:** puede estar en el correo no deseado, el correo no está registrado (por seguridad la pantalla dice lo mismo exista o no) o el envío está fallando.
- **Qué hacer:** revisar la carpeta de no deseado. El código vale 15 minutos y se puede pedir otro a los 60 segundos con **Reenviar**; caben 3 solicitudes cada 15 minutos y solo vale el último código. Confirmar la dirección en **Usuarios**. Si a nadie le llegan correos, revisar la cola de trabajos en **Sistema → Salud** y los trabajos fallidos.
- **Lo resuelve:** la persona; el Super admin si es una falla del envío.
- **Para entenderlo:** [Recuperar la contraseña](../flujos/acceso/recuperar-contrasena.md) y [Jobs fallidos](../flujos/auditoria-y-sistema/jobs-fallidos.md).

### Me sacó de la sesión sin hacer nada

- **Por qué pasa:** la sesión se cierra tras 1 hora sin uso y, en cualquier caso, a las 8 horas. También se cierra al instante si un administrador desactiva la cuenta, si se cambia la contraseña desde otro equipo o si se restablece el acceso.
- **Qué hacer:** volver a ingresar: Trazo regresa a la página donde estaba.
- **Lo resuelve:** la persona.
- **Para entenderlo:** [Sesión: vencimiento, inactividad y sin conexión](../flujos/acceso/sesion.md) · error `UNAUTHENTICATED`.

### Me llegó un aviso de seguridad por correo que no reconozco

- **Por qué pasa:** Trazo avisa cuando cambia la contraseña, se activa o desactiva la verificación en dos pasos, se usa un código de recuperación o se generan códigos nuevos.
- **Qué hacer:** si la persona no hizo ese cambio, restablecer su acceso de inmediato y revisar la auditoría.
- **Lo resuelve:** Super admin.
- **Para entenderlo:** [Restablecer MFA o acceso](../flujos/administracion/restablecer-acceso.md) y [Consultar la auditoría](../flujos/auditoria-y-sistema/auditoria.md).

## Incidentes

### No veo un incidente que alguien me dice que existe

- **Por qué pasa:** cada rol ve una parte. El solicitante ve solo lo que reportó; el agente, lo que reportó, lo que tiene asignado y lo que no tiene responsable; el supervisor, todo. Un incidente eliminado solo aparece con **Ver eliminados**. Trazo responde "no encontrado" tanto si no existe como si la persona no puede verlo.
- **Qué hacer:** confirmar el código del incidente con quien lo menciona y revisar el rol de la persona. Si estaba eliminado, un supervisor puede restaurarlo.
- **Lo resuelve:** Supervisor.
- **Para entenderlo:** [Listado: filtros, búsqueda y grupos](../flujos/incidentes/listado.md) y [Eliminar y restaurar](../flujos/incidentes/eliminar-y-restaurar.md) · error `RESOURCE_NOT_FOUND`.

### No puedo editar un incidente

- **Por qué pasa:** quien lo reportó solo puede editarlo mientras está en **Abierto**, y nadie edita un incidente **Cerrado** o **Cancelado**. Si otra persona lo cambió mientras se editaba, aparece un aviso para recargar antes de guardar.
- **Qué hacer:** si ya lo están atendiendo, pedir el cambio a un supervisor. Ante el aviso de cambios, pulsar **Recargar**.
- **Lo resuelve:** Supervisor.
- **Para entenderlo:** [Editar con bloqueo optimista](../flujos/incidentes/editar.md) · errores `INCIDENT_EDIT_LOCKED`, `INCIDENT_FINALIZED` y `VERSION_CONFLICT`.

### No aparece la opción de cambiar a cierto estado

- **Por qué pasa:** desde cada estado solo se puede pasar a algunos ([ciclo de vida](../producto/ciclo-de-vida.md)). Por ejemplo, de Abierto no se pasa directo a Resuelto: primero va a En progreso. Además, un agente solo cambia el estado de los incidentes que tiene asignados.
- **Qué hacer:** pasar primero por el estado intermedio, o pedir el cambio a quien tiene asignado el incidente.
- **Lo resuelve:** la persona asignada o un supervisor.
- **Para entenderlo:** [Cambiar de estado](../flujos/incidentes/cambiar-estado.md) · error `INVALID_STATUS_TRANSITION`.

### Al asignar, no aparece la persona que busco

- **Por qué pasa:** la lista muestra solo las cuentas activas que pueden atender incidentes.
- **Qué hacer:** revisar en **Usuarios** que la persona esté activa y que su rol incluya atender incidentes.
- **Lo resuelve:** Administrador.
- **Para entenderlo:** [Asignar, tomar y reasignar](../flujos/incidentes/asignar.md).

### La exportación dice que hay demasiadas filas

- **Por qué pasa:** el límite es 50.000 filas por archivo.
- **Qué hacer:** acotar los filtros, por ejemplo con un rango de fechas más corto, y exportar por partes.
- **Lo resuelve:** la persona.
- **Para entenderlo:** [Exportar a CSV](../flujos/incidentes/exportar.md) · error `EXPORT_TOO_LARGE`.

### El archivo exportado se ve mal en Excel

- **Por qué pasa:** el archivo está preparado para abrirse con doble clic en Excel en español (separador `;`, acentos y fechas correctas). Otros programas pueden leerlo con otra configuración.
- **Qué hacer:** si se importa desde otro programa, indicar codificación UTF-8 y separador punto y coma.
- **Lo resuelve:** la persona.
- **Para entenderlo:** [Exportar a CSV](../flujos/incidentes/exportar.md).

## Pantallas

### No veo el botón Personalizar en Inicio

- **Por qué pasa:** el resumen se personaliza solo en portátil y monitor, donde hay espacio para mover y estirar los widgets. En tablet y celular se acomodan solos en el orden elegido.
- **Qué hacer:** personalizarlo desde un portátil o un monitor.
- **Lo resuelve:** la persona.
- **Para entenderlo:** [Personalizar el dashboard](../flujos/resumen/personalizar.md).

### No me aparecen los cambios de otras personas

- **Por qué pasa:** los cambios llegan en vivo, pero si la conexión se interrumpe Trazo sigue funcionando igual y los datos se ven al recargar o al volver a la pantalla. Al reconectarse, Trazo vuelve a consultar lo que se está mostrando.
- **Qué hacer:** recargar la pantalla. Si a nadie le llegan los cambios, revisar **Tiempo real** en **Sistema → Salud**.
- **Lo resuelve:** la persona; el Super admin si le pasa a todos.
- **Para entenderlo:** [Conexión y canales privados](../flujos/avisos/conexion.md) y [Panel de salud](../flujos/auditoria-y-sistema/salud.md).

### Aparece una pantalla de "sin conexión"

- **Por qué pasa:** Trazo no logra comunicarse con el servidor: puede ser la conexión de la persona, una falla del servidor o un mantenimiento.
- **Qué hacer:** revisar la conexión y pulsar **Reintentar**. La sesión sigue abierta.
- **Lo resuelve:** la persona; el Super admin si el servidor no responde.
- **Para entenderlo:** [Sesión: vencimiento, inactividad y sin conexión](../flujos/acceso/sesion.md) · errores `NETWORK_ERROR` y `SERVER_UNREACHABLE`.

### Dice que Trazo está en mantenimiento

- **Por qué pasa:** el Super admin activó el modo mantenimiento mientras hace un ajuste.
- **Qué hacer:** esperar a que lo desactive; la sesión no se pierde.
- **Lo resuelve:** Super admin.
- **Para entenderlo:** [Modo mantenimiento](../flujos/auditoria-y-sistema/mantenimiento.md) · error `SERVICE_UNAVAILABLE`.

### Me falta una opción del menú que otra persona sí tiene

- **Por qué pasa:** el menú muestra solo lo que el rol y los permisos de cada persona permiten.
- **Qué hacer:** si la persona debería tenerla, revisar su rol o concederle el permiso. El cambio se ve al instante, sin volver a entrar.
- **Lo resuelve:** Administrador.
- **Para entenderlo:** [Cambiar rol o permisos](../flujos/administracion/cambiar-rol-o-permisos.md) y [Roles y permisos](../producto/roles.md).

## Para profundizar

- [Catálogo de errores](catalogo-de-errores.md)
- [Roles y permisos](../producto/roles.md)
