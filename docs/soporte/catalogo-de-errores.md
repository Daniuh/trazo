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
| No pudimos procesar la solicitud… / Esta acción no está disponible… | Petición mal formada o método no permitido (poco habitual) | Recargar la página; si se repite, reportar con el código |

## Inicio de sesión

| Mensaje que ve el usuario | Qué significa | Qué hacer |
|---|---|---|
| El correo o la contraseña no son correctos… | La contraseña no coincide, el correo no existe o la cuenta está desactivada. El mensaje es el mismo a propósito, para no revelar qué cuentas existen | Confirmar el correo; si la cuenta debería estar activa, un administrador la revisa |
| Por seguridad, pausamos el ingreso con este correo… Vuelve a intentarlo en N minutos. | Hubo 5 o más intentos fallidos seguidos con ese correo. La espera crece: 1 min, 5 min, 15 min y 1 hora | Esperar el tiempo indicado. Si nadie más conoce la cuenta, puede ser un intento de acceso: avisar al equipo de seguridad |
| Tu sesión terminó. Inicia sesión de nuevo para continuar. (en la pantalla de login) | La sesión venció (8 horas), estuvo más de 1 hora sin uso, se cerró o la cuenta se desactivó | Volver a ingresar; se regresa a la página donde estaba |

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
