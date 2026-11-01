---
title: Glosario
description: Las palabras de Trazo en lenguaje sencillo, con dónde se ven en la aplicación y qué documento las explica.
audience: todos
area: producto
module: glosario
order: 10
---

# Glosario

Las palabras que se usan en Trazo y en esta documentación, explicadas sin tecnicismos. Cada una dice dónde se ve en la aplicación.

## A

### Abierto

El estado con el que nace todo incidente. Mientras sigue en Abierto, quien lo reportó todavía puede editarlo.

Dónde se ve: estado de un incidente. Más en [Ciclo de vida](ciclo-de-vida.md).

### Actividad

Todo lo que le pasó a un incidente, en orden: quién lo creó, quién lo asignó, cada cambio de estado con su nota y los mensajes del chat. También se llama historial.

Dónde se ve: detalle del incidente, "Ver toda la actividad".

### Asignado

La persona que atiende un incidente. Puede no haber nadie: entonces el incidente está "sin asignar".

Dónde se ve: detalle del incidente, listado y Mis incidentes. Más en [Asignar, tomar y reasignar](../flujos/incidentes/asignar.md).

### Auditoría

El registro de quién hizo qué y cuándo, que nadie puede editar ni borrar. Tiene dos partes: incidentes y administración.

Dónde se ve: sección "Auditoría". Más en [Consultar la auditoría](../flujos/auditoria-y-sistema/auditoria.md).

## C

### Cancelado

Estado final: el incidente se cerró sin resolverse. Pide una nota que explique por qué. Solo se sale de él reabriendo.

Dónde se ve: estado de un incidente.

### Cerrado

Estado final: el incidente se resolvió y se confirmó. Solo se sale de él reabriendo.

Dónde se ve: estado de un incidente.

### Código de recuperación

Uno de los 8 códigos de un solo uso que se entregan al activar la verificación en dos pasos. Sirve para entrar sin el teléfono.

Dónde se ve: al configurar la verificación y en Mi cuenta. Más en [Códigos de recuperación](../flujos/acceso/codigos-de-recuperacion.md).

### Código del incidente

El identificador legible de cada incidente, como `TZ-26-0142`: prefijo, año y consecutivo del año. Se puede buscar escribiéndolo, con o sin el prefijo.

Dónde se ve: listado, detalle y avisos.

### Conversación

El chat de cada incidente. Escribir un mensaje no cambia el estado, así que se puede escribir también en incidentes terminados. El mensaje propio se puede borrar durante 5 minutos.

Dónde se ve: detalle del incidente, botón "Chat". Más en [Conversación del incidente](../flujos/incidentes/conversacion.md).

### Crítica

La prioridad más alta. Su plazo por defecto es de 4 horas y avisa a quienes reparten el trabajo en cuanto se crea.

Dónde se ve: prioridad de un incidente.

## D

### Dashboard

La página de inicio con las cifras de la operación: "Mi resumen" para quien ve lo suyo y "Resumen general" para quien ve todo.

Dónde se ve: Inicio. Más en [Cifras del dashboard con caché](../flujos/resumen/cifras.md).

### Dock

La barra flotante de abajo con las secciones de Trazo, la campana y la cuenta. Muestra solo las secciones que la persona puede usar.

Dónde se ve: todas las pantallas con sesión.

## E

### En espera

Estado pendiente: el incidente no avanza porque falta algo (una respuesta, un repuesto). Sigue contando para el vencimiento.

Dónde se ve: estado de un incidente.

### En progreso

Estado pendiente: alguien está trabajando en el incidente.

Dónde se ve: estado de un incidente.

### Evento del sistema

Una falla o una señal de seguridad que Trazo registra para el Super admin, agrupada: si se repite, sube su contador en lugar de llenar la pantalla.

Dónde se ve: Sistema › Eventos. Más en [Registro de eventos del sistema](../flujos/auditoria-y-sistema/eventos.md).

## J

### Jerarquía

El orden de los roles por nivel. Cada persona solo administra a quienes están por debajo de su nivel; el Super admin administra a todos menos a sí mismo.

Dónde se ve: Usuarios y Roles. Más en [Roles y permisos](roles.md).

## M

### Mantenimiento

Un modo que cierra Trazo a todos menos a quien opera el sistema, mientras se hace un ajuste. La sesión de las demás personas no se pierde.

Dónde se ve: aviso en pantalla. Más en [Modo mantenimiento](../flujos/auditoria-y-sistema/mantenimiento.md).

### Mis incidentes

El tablero de quien atiende incidentes, con cuatro vistas: Kanban, Lista, Calendario y Mi día.

Dónde se ve: botón "Mis incidentes" junto a "Nuevo incidente". Más en [Mis incidentes: Kanban](../flujos/incidentes/mis-incidentes.md).

## N

### Notificación

Un aviso en la campana sobre algo que le importa a la persona: un mensaje, una asignación, un cambio de estado o un vencimiento. Nadie recibe aviso de lo que hizo él mismo.

Dónde se ve: la campana del dock. Más en [Notificaciones a participantes](../flujos/avisos/notificaciones.md).

## P

### Pendiente

Un incidente que todavía no termina: Abierto, En progreso o En espera.

Dónde se ve: dashboard, filtros y agrupaciones del listado.

### Permiso

Una acción concreta que alguien puede hacer, como "Asignar incidentes a otros". Los roles son conjuntos de permisos, y a una persona se le pueden conceder o quitar permisos sueltos.

Dónde se ve: Roles y ficha de cada persona. Más en [Roles y permisos](roles.md).

### Personalizar

El modo del dashboard para mover, estirar, quitar y agregar widgets. Se guarda por persona.

Dónde se ve: Inicio, en portátil y monitor. Más en [Personalizar el dashboard](../flujos/resumen/personalizar.md).

### Prioridad

Qué tan urgente es un incidente: Baja, Media, Alta o Crítica. Cada una trae un plazo sugerido para el vencimiento.

Dónde se ve: formulario, listado y detalle.

## R

### Reapertura

Volver a poner en curso un incidente cerrado o cancelado. Cualquiera puede pedirla con un motivo; la decide alguien con ese permiso, que no puede ser quien la pidió.

Dónde se ve: detalle de un incidente terminado. Más en [Solicitar y decidir una reapertura](../flujos/incidentes/reapertura.md).

### Reautenticación

Pedir el código de la aplicación de quien administra antes de un cambio delicado, aunque ya tenga la sesión abierta.

Dónde se ve: diálogo del código en Usuarios, Roles y Sistema. Más en [Cambiar rol o permisos](../flujos/administracion/cambiar-rol-o-permisos.md).

### Resuelto

Estado que indica que el problema se solucionó, a la espera de cerrarse. Pide una nota. Desde aquí se puede cerrar o volver a En progreso.

Dónde se ve: estado de un incidente.

### Rol

El conjunto de permisos de una persona: Solicitante, Agente, Supervisor, Administrador o Super admin, más los roles que se creen.

Dónde se ve: Usuarios y Roles. Más en [Roles y permisos](roles.md).

## S

### Sesión

El tiempo en que una persona está dentro de Trazo después de ingresar. Dura como máximo 8 horas y se cierra tras 1 hora sin uso.

Dónde se ve: ingreso y Mi cuenta. Más en [Sesión: vencimiento, inactividad y sin conexión](../flujos/acceso/sesion.md).

### Sin asignar

Un incidente que nadie tiene. Quien atiende incidentes puede tomarlo; quien coordina puede asignarlo.

Dónde se ve: listado, filtro "Asignado".

## T

### Tiempo real

Que los cambios de otras personas aparezcan sin recargar la página. Si se interrumpe, Trazo sigue funcionando igual.

Dónde se ve: listado, detalle, dashboard y campana. Más en [Un cambio llega a quien puede verlo](../flujos/avisos/cambio-de-incidente.md).

### Tomar

Asignarse a uno mismo un incidente que no tiene a nadie.

Dónde se ve: botón "Tomar" en el detalle. Más en [Asignar, tomar y reasignar](../flujos/incidentes/asignar.md).

## V

### Vencido

Un incidente pendiente cuyo vencimiento ya pasó. No se guarda: se calcula en cada consulta.

Dónde se ve: listado, dashboard y avisos. Más en [Vencidos cada 5 minutos](../flujos/avisos/vencidos.md).

### Vencimiento

La fecha y hora límite para resolver un incidente. Si quien lo crea no la indica, Trazo la calcula con el plazo de la prioridad.

Dónde se ve: formulario y detalle.

### Verificación en dos pasos

Además de la contraseña, un código de 6 dígitos de una aplicación del teléfono. También se llama MFA. Todos los roles la exigen después del primer ingreso.

Dónde se ve: ingreso y Mi cuenta. Más en [Configurar la verificación en dos pasos](../flujos/acceso/configurar-mfa.md).

### Versión

Un número que sube con cada cambio de un incidente. Si dos personas editan a la vez, la segunda recibe un aviso para recargar en lugar de pisar el cambio de la primera.

Dónde se ve: aviso "Alguien más guardó cambios". Más en [Editar con bloqueo optimista](../flujos/incidentes/editar.md).
