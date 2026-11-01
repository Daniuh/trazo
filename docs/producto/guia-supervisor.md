---
title: Guía del supervisor
description: Repartir el trabajo, vigilar los vencidos, leer el resumen general, personalizarlo, exportar y revisar la auditoría.
audience: directivo
area: producto
module: guias
order: 7
---

# Guía del supervisor

El supervisor ve todos los incidentes, reparte el trabajo y sigue la operación con cifras. Puede hacer todo lo del [agente](guia-agente.md), y además lo que se describe aquí.

## Repartir el trabajo

- En un incidente, pulsa **Asignar** (o **Reasignar** si ya tiene responsable) y elige a la persona. Solo aparecen las personas activas que pueden atender incidentes.
- La persona recibe el aviso al momento.
- Para ver lo que nadie atiende, filtra el listado por **Asignado → Sin asignar**.

## Mis incidentes

En **Incidentes**, el botón **Mis incidentes** abre lo que tienes asignado: lo que sigue en curso y lo que se cerró o canceló en los últimos 7 días. Arriba eliges cómo verlo:

- **Kanban:** una columna por estado. En el computador, arrastra una tarjeta a otra columna para cambiar su estado; las columnas a las que puede ir se marcan y las demás se apagan. En el celular y la tablet, elige la columna en las pestañas y usa **⋯ → Mover a**. Como en el detalle, Resuelto y Cancelado piden una nota.
- **Lista:** la misma tabla del listado, solo con lo tuyo. Toca un encabezado para ordenar.
- **Calendario:** cada incidente en el día en que vence. Cambia de mes con las flechas o vuelve con **Hoy**. En el computador ves el mes completo (si un día tiene muchos, **+N más** los muestra todos); en el celular, una agenda con los días que tienen algo.
- **Mi día:** lo de hoy de un vistazo, ideal para la reunión diaria: vencidos, vencen hoy, en progreso, en espera y lo que resolviste ayer.

La vista que elijas queda guardada para la próxima vez.

## Vigilar lo urgente

- **Más filtros → Solo vencidos** muestra los pendientes que ya pasaron su fecha.
- **Vence entre** sirve para planear lo que vence esta semana.
- Ordena por prioridad para atender primero lo crítico.

Los filtros quedan en la dirección de la página: se puede guardar como favorito o compartir un listado ya filtrado.

## El resumen general

**Inicio** muestra el **Resumen general** de toda la operación:

| Widget | Qué muestra |
|---|---|
| Pulso de la operación | En una frase, cuántos vencidos hay y si hoy el backlog sube o baja. El cumplimiento del plazo (qué parte de lo resuelto en 30 días fue a tiempo) y el total, los pendientes y los de prioridad Crítica |
| Mapa de riesgo | Los pendientes por prioridad y vencimiento (vencido, hoy, 7 días, después), con el color más intenso donde más se acumulan, y el total de cada prioridad. Toca una celda para ver esos incidentes |
| Flujo de estados | Cuántos hay en cada etapa, de Abierto a Cerrado, y cuántos salieron cancelados |
| Balance del backlog | Si los pendientes suben o bajan día a día, en 7, 30 o 90 días |
| Próximos a vencer | Los pendientes que vencen en las próximas 48 horas |
| Asignados a mí | Los pendientes propios, del que vence antes al que vence después |
| Carga por agente | Pendientes y vencidos de cada persona, para equilibrar el trabajo |

Con **Personalizar** (en un computador) se pueden agregar los indicadores clásicos (total, pendientes, vencidos, sin asignar), los gráficos por estado y por prioridad y creados contra resueltos.

Cada cifra es un enlace: al pulsarla se abre el listado con ese filtro ya aplicado. Las cifras se actualizan solas cuando cambia algún incidente.

### Personalizar el resumen

En portátil y monitor, pulsa **Personalizar** para mover los widgets, cambiarles el tamaño, quitarlos o agregar otros. Pulsa **Guardar** para conservar el diseño o **Restaurar original** para volver al de fábrica. El diseño es personal: cada persona tiene el suyo, y se respeta en todos sus equipos. En tablet y celular los widgets se acomodan solos en el orden elegido.

## Exportar a Excel

En el listado, aplica los filtros que necesites y pulsa **Exportar** (en celular y tablet está en el menú **⋯**). Trazo confirma cuántas filas va a exportar y descarga un archivo CSV que Excel abre directamente, con acentos y fechas correctas. El límite es de 50.000 filas por archivo: si el filtro trae más, Trazo pide acotarlo. Cada exportación queda registrada en la auditoría.

## Conversar sobre un incidente

En la ficha de cada incidente, el botón **Chat** abre un panel con la conversación con quien lo reportó y quien lo atiende. Escribe tu pregunta o aclaración al pie y pulsa Enter para enviarla (Shift + Enter hace un salto de línea). Los mensajes de los demás llegan solos, sin recargar. Si tocas un mensaje en el **historial** del incidente, el chat se abre justo en ese mensaje.

- Se puede escribir también en un incidente cerrado o cancelado, por ejemplo para preguntar si quedó bien. Eso no cambia su estado.
- Si te equivocas, durante los primeros 5 minutos puedes **Borrar** tu mensaje; en su lugar queda "Mensaje eliminado".

## Notificaciones

La **campana**, en el menú de abajo junto a tus iniciales, muestra cuántos avisos tienes sin leer. Al tocarla se abre el centro de notificaciones con dos pestañas:

- **Conversaciones:** respuestas en las conversaciones de los incidentes en los que participas. Si llegan varios mensajes del mismo incidente, se juntan en un solo aviso.
- **Actividad:** cuando te asignan un incidente y cuando cambia el estado de los que creaste o tienes a tu cargo. Además, como repartes el trabajo, te llegan los **avisos de coordinación**:
  - **Nuevo sin asignar:** alguien reportó un incidente y nadie lo tiene.
  - **Crítico reportado:** se reportó un incidente de prioridad Crítica (dice si sigue sin asignar).
  - **Pidió reabrirlo:** alguien solicitó reabrir un incidente y espera tu decisión.
  - **Venció sin resolverse:** un incidente pendiente pasó su fecha de vencimiento (te llega una sola vez por vencimiento).

  Nunca recibes aviso de lo que hiciste tú mismo.

Toca un aviso para ir al incidente; si es un mensaje, la conversación se abre sola. **Marcar todo como leído** limpia la pestaña. Los avisos se guardan 90 días.

## Reaperturas

Lo cerrado o cancelado solo se reabre con tu aprobación. En el listado, los que esperan respuesta dicen **Reapertura pendiente** debajo del estado, y **Más filtros → Con reapertura pendiente** muestra solo esos. La solicitud aparece en el detalle (y, en monitor, en la vista previa del listado), con quién la pidió y por qué:

- **Aprobar y reabrir:** vuelve a **En progreso** si tiene responsable, o a **Abierto** si no, con un vencimiento nuevo.
- **Rechazar:** escribe el motivo; el incidente sigue como estaba y el motivo queda en el historial.

No puedes decidir una solicitud que hiciste tú. También puedes **Reabrir** directamente un incidente terminado, escribiendo el motivo.

## Eliminar y restaurar

Eliminar un incidente lo saca de los listados y del resumen, pero no lo borra. En **Más filtros → Ver eliminados** aparecen los eliminados, cada uno con su botón **Restaurar**.

## Auditoría de incidentes

En **Auditoría → Incidentes** aparece todo lo que pasó en todos los incidentes, incluidos los eliminados: quién hizo cada cambio, cuándo y desde qué dirección y navegador. Se puede filtrar por fechas (los últimos 30 días por defecto), por persona, por tipo de cambio o por un incidente puntual (`TZ-26-0142`, o solo `26-142`).

## Para profundizar

- [Ciclo de vida de un incidente](ciclo-de-vida.md)
- [Roles y permisos](roles.md)
