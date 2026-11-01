---
title: Preguntas frecuentes
description: Las consultas más comunes de las personas usuarias, con su causa y cómo resolverlas.
audience: soporte
area: soporte
order: 3
---

# Preguntas frecuentes

Respuestas para atender a una persona sin entrar en detalles técnicos. Si el caso muestra un mensaje de error, el [catálogo de errores](catalogo-de-errores.md) explica cada uno.

## Ingreso y cuenta

**No puedo entrar y la contraseña está bien.**
El mensaje de ingreso es el mismo para un correo inexistente, una contraseña incorrecta o una cuenta desactivada, para no revelar qué cuentas existen. Revisa en **Usuarios** que la cuenta exista con ese correo y que esté activa.

**Dice que el ingreso está pausado.**
Hubo 5 o más intentos fallidos seguidos con ese correo. La espera crece con cada tanda de intentos: 1 minuto, 5, 15 y luego 1 hora. Hay que esperar el tiempo que indica el mensaje. Si la persona no hizo esos intentos, alguien puede estar probando su cuenta: conviene que el Super admin revise los eventos de seguridad en **Sistema → Eventos** y, si hace falta, restablezca el acceso.

**El código de la aplicación de autenticación siempre sale incorrecto.**
Casi siempre es la hora del teléfono. Los códigos dependen de la hora exacta: en los ajustes del teléfono hay que activar la fecha y hora automáticas. También pasa si se escribe un código que ya se usó; basta esperar al siguiente (cambia cada 30 segundos).

**Perdí el teléfono (o cambié de teléfono).**
- Si la persona guardó sus **códigos de recuperación**, puede entrar con uno de ellos y volver a configurar la verificación desde **Mi cuenta**.
- Si no los tiene, el **Super admin** restablece su verificación en dos pasos desde la ficha de la persona. En el siguiente ingreso la configura de nuevo.

**No me llega el correo con el código de recuperación.**
- Revisar la carpeta de correo no deseado.
- El código vale 15 minutos. Se puede pedir otro después de 60 segundos con **Reenviar**. Caben 3 solicitudes cada 15 minutos, contando la primera, y solo vale el último código enviado.
- Por seguridad, la pantalla dice lo mismo exista o no el correo: si el correo no está registrado, no llega nada. Confirma la dirección en **Usuarios**.
- Si a nadie le llegan correos, puede ser una falla del envío: el Super admin lo ve en **Sistema → Salud** (cola de trabajos) y en los trabajos fallidos.

**Me sacó de la sesión sin hacer nada.**
La sesión se cierra tras 1 hora sin uso y, en cualquier caso, a las 8 horas. También se cierra al instante si un administrador desactiva la cuenta, si se cambia la contraseña desde otro equipo o si se restablece el acceso. Basta volver a ingresar: Trazo regresa a la página donde estaba.

**Me llegó un aviso de seguridad por correo que no reconozco.**
Trazo avisa cuando cambia la contraseña, se activa o desactiva la verificación en dos pasos, se usa un código de recuperación o se generan códigos nuevos. Si la persona no hizo ese cambio, el Super admin debe **restablecer su acceso** de inmediato y revisar la auditoría.

## Incidentes

**No veo un incidente que alguien me dice que existe.**
Cada rol ve una parte. El solicitante ve solo lo que reportó; el agente, lo que reportó, lo que tiene asignado y lo que no tiene responsable; el supervisor, todo. Si el incidente se eliminó, solo aparece con **Ver eliminados** (supervisor y Super admin). Trazo responde "no encontrado" tanto si no existe como si la persona no puede verlo.

**No puedo editar un incidente.**
- Quien lo reportó solo puede editarlo mientras está en **Abierto**.
- Nadie puede editar un incidente **Cerrado** o **Cancelado**: son estados finales.
- Si otra persona lo cambió mientras se editaba, aparece un aviso para recargar antes de guardar.

**No aparece la opción de cambiar a cierto estado.**
Desde cada estado solo se puede pasar a algunos ([ciclo de vida](../producto/ciclo-de-vida.md)). Por ejemplo, de Abierto no se pasa directo a Resuelto: primero va a En progreso. Además, un agente solo cambia el estado de los incidentes que tiene asignados.

**Al asignar, no aparece la persona que busco.**
La lista muestra solo las cuentas activas que pueden atender incidentes. Si la persona está desactivada o su rol no incluye atender incidentes, no aparece.

**La exportación dice que hay demasiadas filas.**
El límite es 50.000 filas por archivo. Hay que acotar los filtros, por ejemplo con un rango de fechas más corto, y exportar por partes.

**El archivo exportado se ve mal en Excel.**
El archivo está preparado para Excel en español: separador `;`, acentos y fechas correctas al abrirlo con doble clic. Si se importa desde otro programa, hay que indicar codificación UTF-8 y separador punto y coma.

## Pantallas

**No veo el botón Personalizar en Inicio.**
El resumen se personaliza solo en portátil y monitor, donde hay espacio para mover y estirar los widgets. En tablet y celular se acomodan solos en el orden elegido.

**No me aparecen los cambios de otras personas.**
Los cambios llegan en vivo, pero si la conexión se interrumpe Trazo sigue funcionando igual: los datos se ven al recargar o al volver a la pantalla. Al reconectarse, Trazo vuelve a consultar lo que se está mostrando. Si a nadie le llegan los cambios, el Super admin revisa **Tiempo real** en **Sistema → Salud**.

**Aparece una pantalla de "sin conexión".**
Trazo no logra comunicarse con el servidor. Si la conexión de la persona funciona y la pantalla sigue, puede ser una falla del servidor o un mantenimiento: el botón **Reintentar** vuelve a probar.

**Dice que Trazo está en mantenimiento.**
El Super admin activó el modo mantenimiento mientras hace un ajuste. Hay que esperar a que lo desactive; la sesión no se pierde.

**Me falta una opción del menú que otra persona sí tiene.**
El menú muestra solo lo que el rol y los permisos de cada persona permiten. Si la persona debería tenerla, un administrador revisa su rol o le concede el permiso. El cambio se ve al instante, sin volver a entrar.

## Para profundizar

- [Catálogo de errores](catalogo-de-errores.md)
- [Roles y permisos](../producto/roles.md)
