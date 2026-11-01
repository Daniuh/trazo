---
title: Qué es Trazo
description: El problema que resuelve Trazo, lo que permite hacer y cómo se protege la información.
audience: directivo
area: producto
module: general
order: 2
---

# Qué es Trazo

Trazo es un sistema para reportar, atender y seguir solicitudes e incidentes. Una persona reporta un problema; el equipo lo toma, lo atiende y lo cierra; y quien dirige ve en todo momento cuántos casos hay, en qué estado están y cuáles van tarde.

## Qué resuelve

Sin una herramienta común, los casos llegan por correo, chat o de palabra: se pierden, nadie sabe quién los tiene y no hay forma de medir si se atienden a tiempo. Trazo pone cada caso en un solo lugar, con una persona responsable, una prioridad, una fecha de vencimiento y un historial de todo lo que pasó.

## Lo que permite hacer

| Necesidad | Cómo la cubre Trazo |
|---|---|
| Reportar un problema | Un formulario con título, descripción y prioridad. La fecha de vencimiento se sugiere sola según la prioridad |
| Saber quién se encarga | Cada incidente tiene una persona asignada, que lo toma o a quien se lo asigna su supervisor |
| Seguir el avance | Seis estados, del reporte al cierre, con reglas sobre a qué estado se puede pasar desde cada uno ([ciclo de vida](ciclo-de-vida.md)) |
| Encontrar casos rápido | Búsqueda por texto o por código (`INC-000123`) y filtros por estado, prioridad, responsable y fechas |
| Ver cómo va la operación | Un resumen con totales, vencidos, casos por estado y por prioridad, y la tendencia de creados contra resueltos |
| Enterarse sin recargar | Los cambios de otras personas aparecen en pantalla al momento |
| Llevar los datos a Excel | Exportación del listado filtrado a un archivo que Excel abre directamente |
| Saber quién hizo qué | Historial de cada incidente y una auditoría general de los cambios y de la administración |

Funciona igual en celular, tablet, portátil y monitor: cada pantalla se acomoda al tamaño disponible.

## Quién lo usa

Hay cinco roles, de menos a más permisos: Solicitante, Agente, Supervisor, Administrador y Super admin. Cada persona ve solo lo que le corresponde. Un solicitante, por ejemplo, ve sus propios incidentes y no los de los demás. El detalle está en [roles y permisos](roles.md).

## Cómo protege la información

- **Ingreso con dos pasos.** Además de la contraseña, cada persona confirma su ingreso con un código de la aplicación de autenticación de su teléfono. Si alguien roba una contraseña, no le basta para entrar.
- **Contraseñas fuertes.** Mínimo 12 caracteres. Se rechazan las contraseñas conocidas por filtraciones públicas, y los intentos fallidos seguidos bloquean el ingreso por un tiempo que va creciendo.
- **Cada quien ve lo suyo.** Los permisos se revisan en el servidor en cada acción, no solo en la pantalla.
- **Todo queda registrado.** Cada cambio de un incidente y cada acción de administración dejan constancia de quién, cuándo y desde dónde.
- **Avisos por correo.** La persona recibe un aviso cuando cambia su contraseña, se activa o desactiva su verificación en dos pasos o se modifica su cuenta.

## Pensado para crecer

Trazo está probado con 100.000 incidentes: el listado y el resumen responden en milisegundos con ese volumen. Los estados, las prioridades, los roles y los permisos son datos que se pueden ajustar sin modificar el sistema.

## Para profundizar

- [Roles y permisos](roles.md)
- [Ciclo de vida de un incidente](ciclo-de-vida.md)
- Guías de uso: [solicitante](guia-solicitante.md), [agente](guia-agente.md), [supervisor](guia-supervisor.md) y [administración](guia-administracion.md)
- [Cumplimiento de la prueba técnica](cumplimiento-de-la-prueba.md)
