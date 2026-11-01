---
title: Catálogo de errores
description: Qué ve el usuario en cada error, qué significa y qué hacer.
audience: soporte
area: soporte
order: 2
---

# Catálogo de errores

Cada mensaje que puede ver un usuario, qué lo provoca y cómo ayudarle. Cuando un mensaje incluye un **código de soporte**, ese código permite encontrar el caso exacto en los logs del sistema.

## Errores generales

| Mensaje que ve el usuario | Qué significa | Qué hacer |
|---|---|---|
| Tu sesión terminó. Inicia sesión de nuevo para continuar. | La sesión expiró o se cerró desde otro lugar | Volver a iniciar sesión |
| No tienes permiso para hacer esta acción… | Su rol no incluye esa acción | Si debería tenerla, un administrador revisa su rol |
| No encontramos lo que buscas… | El registro no existe, se eliminó o no tiene acceso a él | Confirmar que el registro exista y que el usuario deba verlo |
| Hay datos por corregir. Revisa los campos marcados. | Uno o más campos del formulario no son válidos | Revisar los mensajes junto a cada campo |
| Hiciste demasiados intentos seguidos. Espera N segundos… | Se superó el límite de intentos (protección contra abusos) | Esperar el tiempo indicado |
| Trazo está en mantenimiento… | El sistema está en modo mantenimiento | Esperar; un super admin puede confirmar el estado |
| No pudimos completar la acción por un problema de nuestro lado… código: X | Falla inesperada del servidor | Pedir el código y buscarlo en los logs del sistema |
| No pudimos conectarnos… | El navegador no llegó al servidor | Revisar la conexión del usuario |
| El servidor tardó demasiado en responder… | La respuesta superó el tiempo de espera | Reintentar; si se repite, revisar la salud del sistema |
| El servidor de Trazo no está respondiendo en este momento… | El gateway respondió 502 o 504: la API está caída, reiniciándose o no contesta | Reintentar en unos minutos; si sigue, revisar que la API esté arriba (salud del sistema) |
| No pudimos procesar la solicitud… / Esta acción no está disponible… | Petición mal formada o método no permitido (poco habitual) | Recargar la página; si se repite, reportar con el código |

## Inicio de sesión

| Mensaje que ve el usuario | Qué significa | Qué hacer |
|---|---|---|
| El correo o la contraseña no son correctos… | La contraseña no coincide, el correo no existe o la cuenta está desactivada. El mensaje es el mismo a propósito, para no revelar qué cuentas existen | Confirmar el correo; si la cuenta debería estar activa, un administrador la revisa |
| Por seguridad, pausamos el ingreso con este correo… Vuelve a intentarlo en N minutos. | Hubo 5 o más intentos fallidos seguidos con ese correo. La espera crece: 1 min, 5 min, 15 min y 1 hora | Esperar el tiempo indicado. Si nadie más conoce la cuenta, puede ser un intento de acceso: avisar al equipo de seguridad |
| Tu sesión terminó. Inicia sesión de nuevo para continuar. (en la pantalla de login) | La sesión venció (8 horas), estuvo más de 1 hora sin uso, se cerró o la cuenta se desactivó | Volver a ingresar; se regresa a la página donde estaba |

## Verificación en dos pasos

| Mensaje que ve el usuario | Qué significa | Qué hacer |
|---|---|---|
| El código no es correcto… Te quedan N intentos. | El código no coincide con el de la aplicación | Escribir el código que muestra ahora la aplicación. Si siempre falla, revisar que la hora del teléfono sea automática |
| Ese código ya se usó… | Era un código correcto, pero ya se había usado (cada uno sirve una vez) | Esperar a que la aplicación muestre uno nuevo (cambia cada 30 segundos) |
| Ese código de recuperación no es válido o ya se usó… | El código no existe, está incompleto o ya se usó | Revisar que tenga sus 10 caracteres; probar con otro de los guardados |
| Por seguridad cerramos este ingreso después de varios códigos incorrectos… | 5 códigos incorrectos en el mismo ingreso | Volver a ingresar con correo y contraseña |
| Pasó demasiado tiempo desde que escribiste tu contraseña… | El segundo paso tiene 5 minutos | Volver a ingresar |
| Tu rol exige la verificación en dos pasos, así que no se puede desactivar. | Se intentó desactivar el MFA de un rol que lo exige | Ninguna; si el teléfono se perdió, ingresar con un código de recuperación y configurarlo de nuevo |
| Tu cuenta ya tiene activa la verificación en dos pasos. / Tu cuenta no tiene activa… / La configuración no está en curso… / Ya ingresaste… | El estado cambió en otra pestaña o la página quedó desactualizada | Recargar la página |

**Si alguien perdió su teléfono y sus códigos de recuperación,** no puede entrar solo. Un administrador tendrá que restablecer su MFA (llega con la administración de usuarios).

## Contraseña

| Mensaje que ve el usuario | Qué significa | Qué hacer |
|---|---|---|
| El código no es correcto o ya venció… | El código del correo está mal escrito, venció (15 minutos), se pidió uno más nuevo o se agotaron los intentos. Si el correo no tiene cuenta, el mensaje es el mismo a propósito | Usar el código del último correo o pedir uno nuevo. Si no llega, revisar el correo no deseado |
| Pasó demasiado tiempo o el código ya se usó… | Pasaron más de 10 minutos desde que se verificó el código, o la solicitud se anuló por intentos fallidos | Pedir un código nuevo desde "¿Olvidaste tu contraseña?" |
| Tu cuenta tiene la verificación en dos pasos: escribe el código… | La cuenta tiene MFA y no se escribió ningún código | Escribir el código de la aplicación o, si no se tiene el teléfono, uno de los códigos de recuperación |
| Esta contraseña aparece en filtraciones conocidas o es de las más usadas… | La contraseña está en filtraciones públicas o entre las más comunes, aunque cumpla el largo | Elegir otra, por ejemplo varias palabras que solo la persona relacione |
| La contraseña actual no es correcta. | Al cambiarla desde Mi cuenta, la contraseña actual no coincide | Escribirla de nuevo; si se olvidó, cerrar sesión y usar "¿Olvidaste tu contraseña?" |
| La contraseña nueva debe ser distinta de la actual. | Se escribió la misma contraseña | Elegir una distinta |
| Hiciste demasiados intentos seguidos… | Más de 3 códigos pedidos en 15 minutos, o más de 5 intentos por minuto | Esperar el tiempo indicado |

Después de recuperar la contraseña se cierran todas las sesiones abiertas; después de cambiarla desde Mi cuenta, todas menos la actual. En los dos casos llega un correo "Tu contraseña cambió". Si la persona no lo hizo, debe recuperar la contraseña de inmediato y avisar al equipo de seguridad.

## Administración de usuarios

| Mensaje que ve el usuario | Qué significa | Qué hacer |
|---|---|---|
| Tu propio rol, tus permisos, tu estado y tu acceso los cambia otra persona… | Se intentó cambiar la cuenta propia (solo se pueden corregir los datos propios) | Pedírselo a otra persona que administre Trazo |
| Esta persona tiene un rol de tu mismo nivel o superior… | Solo se administra hacia abajo: un Administrador no toca a otro Administrador ni al Super admin | Pedírselo al Super admin |
| No puedes dar ese rol: solo puedes asignar roles por debajo del tuyo. | El rol elegido está en su nivel o por encima | Elegir otro rol, o pedírselo al Super admin |
| Solo puedes conceder permisos que tú también tienes… | Se marcó un permiso que quien administra no tiene | Desmarcarlo; quitar permisos sí se puede |
| El Super admin tiene todos los permisos por su rol… | Al Super admin no se le conceden ni quitan permisos uno por uno | Ninguna; si debe tener menos acceso, cambiarle el rol |
| Trazo necesita al menos un Super admin activo… | El cambio dejaría el sistema sin Super admin | Primero dar el rol Super admin a otra persona |
| Esta cuenta ya está activa / desactivada… · Esta persona no tiene activa la verificación en dos pasos… | La ficha estaba desactualizada (otra persona hizo el cambio) | Recargar la página |
| Para confirmar este cambio, escribe el código de tu aplicación de autenticación. | El cambio toca permisos de administración o restablece un acceso | Escribir el código en el diálogo que se abre |
| Para hacer este cambio necesitas tener activa la verificación en dos pasos… | Quien administra no tiene MFA, así que no puede confirmar acciones sensibles | Activarla desde Mi cuenta |
| Escribiste varios códigos incorrectos seguidos. Espera N y vuelve a intentarlo. | Cinco códigos incorrectos en un minuto | Esperar; revisar que la hora del teléfono sea automática |
| Este valor ya está en uso. Prueba con otro. (junto al correo) | El correo ya es de otra cuenta | Usar otro correo, o buscar la cuenta existente en el listado |

**Si alguien perdió su teléfono y sus códigos de recuperación,** el Super admin restablece su verificación en dos pasos desde su ficha; en el siguiente ingreso la configura de nuevo. **Si no puede ingresar por la contraseña,** puede usar "¿Olvidaste tu contraseña?" o el Super admin puede restablecer su acceso.

## Roles

| Mensaje que ve el usuario | Qué significa | Qué hacer |
|---|---|---|
| Este rol está en tu nivel o por encima, así que no lo puedes cambiar. | Solo se editan los roles por debajo del nivel de quien administra | Pedírselo al Super admin |
| El rol Super admin tiene siempre todos los permisos, así que no se edita ni se elimina. | El rol de la cima está bloqueado para todos | Ninguna; es el comportamiento esperado |
| Este es uno de los roles base de Trazo, así que no se puede eliminar… | Los cinco roles de sistema se editan, pero no se eliminan | Editarlo, o crear un rol nuevo |
| Hay personas con este rol. Cámbiales el rol antes de eliminarlo. | Un rol en uso no se elimina | Cambiar el rol de esas personas desde el menú Usuarios |
| Elige un nivel por debajo del tuyo: como máximo N. | El nivel elegido está en el de quien administra o por encima | Elegir un nivel menor |
| Este valor ya está en uso. Prueba con otro. (junto al código o al nivel) | Ya existe un rol con ese código, o con ese nivel (cada nivel es de un solo rol) | Elegir otro código u otro nivel |
| Usa solo letras minúsculas sin tildes, números y guion bajo… | El código del rol tiene un formato no válido | Corregirlo, por ejemplo `soporte_n2` |
| Para confirmar este cambio, escribe el código de tu aplicación de autenticación. | El cambio toca permisos de administración, cambia el nivel o relaja la verificación en dos pasos del rol | Escribir el código en el diálogo que se abre |

Los cambios de un rol aplican de inmediato a todas las personas que lo tienen; no se les envía correo. Cada creación, edición o eliminación queda registrada con quién la hizo.

## Auditoría

| Mensaje que ve el usuario | Qué significa | Qué hacer |
|---|---|---|
| Escribe el código del incidente, por ejemplo INC-000123 o solo 123. | El filtro de incidente no tiene forma de código | Corregirlo o tocar "Quitar el código" |
| No pudimos seguir cargando la lista. Vuelve a cargarla desde el principio. | El enlace para seguir la lista no era válido (por ejemplo, se alteró a mano) | Tocar "Reintentar" o recargar la página |

- **"No hay registros en los últimos 30 días":** sin fechas, la auditoría muestra solo los últimos 30 días. Para ver lo anterior, elegir un rango más amplio.
- **Alguien no ve la pestaña de Incidentes o la de Administración:** cada una tiene su permiso. El Supervisor ve la de incidentes y el Administrador la de administración. El Super admin ve las dos, y cualquiera de ellas se puede conceder a una persona puntual.
- **Un administrador no encuentra una acción:** solo ve lo que hizo y lo que toca a personas o roles por debajo de su nivel. Lo demás lo ve el Super admin.

## Incidentes

| Mensaje que ve el usuario | Qué significa | Qué hacer |
|---|---|---|
| Alguien más guardó cambios en este incidente mientras lo tenías abierto… | Otra persona guardó un cambio antes (conflicto de versión) | Pulsar "Recargar", revisar la versión actual y repetir el cambio si sigue siendo necesario |
| Este incidente ya está cerrado o cancelado… | Está en un estado final | Si de verdad necesita cambios, crear un incidente nuevo |
| Este incidente ya lo están atendiendo, así que ya no puedes editarlo… | Quien lo creó intenta editarlo cuando ya pasó del estado Abierto | Pedir el cambio a un supervisor |
| Otra persona tomó este incidente hace un momento… | Dos personas intentaron tomarlo a la vez | Recargar para ver quién lo tiene |
| Ese cambio de estado no está permitido desde el estado actual… | El estado cambió mientras tanto y esa transición ya no aplica | Recargar para ver las opciones vigentes |
| Esta persona no puede recibir incidentes… | La persona elegida se desactivó o su rol ya no atiende incidentes | Elegir a otra persona de la lista |
| Tu incidente se está creando… | El mismo envío llegó dos veces muy seguidas | Esperar unos segundos; no se crea duplicado |
| No pudimos confirmar el envío… / No pudimos procesar el envío… | Problema con la clave de idempotencia (poco habitual) | Recargar la página e intentarlo de nuevo |
| Con estos filtros salen 60.000 incidentes y el máximo por archivo es 50.000… | La exportación pasa del tope de filas | Acotar los filtros (por ejemplo, un rango de fechas) y exportar por partes |
| Hiciste demasiados intentos seguidos. Espera… (al exportar) | Más de 5 exportaciones en un minuto | Esperar el tiempo indicado |

## Sistema

| Mensaje que ve el usuario | Qué significa | Qué hacer |
|---|---|---|
| Trazo está en mantenimiento. Vuelve a intentarlo en unos minutos. | El Super admin activó el modo mantenimiento | Esperar y pulsar "Reintentar"; quien administra el sistema puede desactivarlo desde Sistema |
| El modo mantenimiento ya estaba activo… / ya estaba apagado… | Otra persona lo cambió mientras el panel estaba abierto | Actualizar el panel |
| No hay jobs fallidos para reintentar. | Se pidió "Reintentar todos" sin ninguno en la lista | Nada; la cola está al día |
| Ese job ya no está en la lista… | Otra persona lo reintentó o lo descartó | Actualizar la lista |
| Ese evento ya no existe… | Se borró por antigüedad (resuelto hace más de 90 días) | Actualizar la lista |
| Hiciste demasiados intentos seguidos. Espera… (en Sistema) | Más de 5 acciones de sistema en un minuto | Esperar el tiempo indicado |

## Dashboard

| Código | Cuándo aparece | Mensaje |
|---|---|---|
| `WIDGET_NOT_AVAILABLE` | Se pidió o guardó un widget que la cuenta ya no puede usar | Uno de los widgets ya no está disponible para tu cuenta. Recarga la página para ver tu dashboard actual. |
| `WIDGET_DUPLICATED` | El diseño enviado repite un widget | Un widget quedó repetido en el diseño. Recarga la página y vuelve a acomodarlo. |
| `WIDGET_OUT_OF_GRID` | Un widget se sale de las 12 columnas | Un widget quedó fuera del espacio del dashboard. Muévelo dentro de la grilla e inténtalo de nuevo. |
| `WIDGET_SIZE_OUT_OF_RANGE` | Un widget es más grande o más pequeño de lo permitido | Un widget quedó más grande o más pequeño de lo permitido. Ajusta su tamaño e inténtalo de nuevo. |
| `WIDGETS_OVERLAP` | Dos widgets visibles se cruzan | Dos widgets quedaron uno encima del otro. Sepáralos e inténtalo de nuevo. |

## Mensajes junto a un campo

| Mensaje | Causa |
|---|---|
| Este campo es obligatorio. | Campo vacío |
| Escribe un texto válido. / un número entero. / un número válido. | Tipo de dato incorrecto |
| Elige sí o no. / Elige al menos una opción válida. | Valor no permitido en una opción |
| Escribe un correo válido, por ejemplo nombre@empresa.com. | Formato de correo |
| Escribe una fecha válida. | Formato de fecha |
| Debe tener al menos N caracteres. / Puede tener máximo N caracteres. | Longitud |
| Debe estar entre N y M. / Debe tener exactamente N caracteres. | Rango o tamaño |
| Elige una de las opciones disponibles. | Valor fuera de la lista |
| La opción elegida ya no existe… / no es válida… | Referencia a algo eliminado o inexistente |
| Este valor ya está en uso. Prueba con otro. | Valor que debe ser único (p. ej. correo) |
| Los valores no coinciden… | Confirmación distinta (p. ej. contraseña) |
| La fecha no puede estar en el pasado. / debe ser posterior a… / anterior a… | Fecha fuera del rango permitido |
| Este campo no se puede modificar. | Campo bloqueado para esa operación |
| Revisa este dato: no es válido. | Regla sin mensaje propio (se debe agregar al catálogo) |

Los mensajes de cada funcionalidad (incidentes, sesión, usuarios…) se agregan a este catálogo cuando se construye esa funcionalidad.

## Dónde está en el código

- [`frontend/src/shared/errors/generalMessages.ts`](../../frontend/src/shared/errors/generalMessages.ts)
- [`frontend/src/shared/errors/fieldMessages.ts`](../../frontend/src/shared/errors/fieldMessages.ts)
- Códigos del backend: [Errores de la API](../api/errores.md)
