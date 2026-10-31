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

## Vigilar lo urgente

- **Más filtros → Solo vencidos** muestra los pendientes que ya pasaron su fecha.
- **Vence entre** sirve para planear lo que vence esta semana.
- Ordena por prioridad para atender primero lo crítico.

Los filtros quedan en la dirección de la página: se puede guardar como favorito o compartir un listado ya filtrado.

## El resumen general

**Inicio** muestra el **Resumen general** de toda la operación:

| Widget | Qué muestra |
|---|---|
| Total de incidentes, Pendientes, Vencidos, Sin asignar | Las cifras clave del momento |
| Por estado | Cuántos hay en cada estado |
| Por prioridad | Cuántos hay de cada prioridad y qué porcentaje representan |
| Creados vs. resueltos | Si el equipo va al día: creados y resueltos por día en 7, 30 o 90 días |
| Próximos a vencer | Los pendientes que vencen en las próximas 48 horas |
| Asignados a mí | Los pendientes propios, del que vence antes al que vence después |
| Carga por agente | Pendientes y vencidos de cada persona, para equilibrar el trabajo |

Cada cifra es un enlace: al pulsarla se abre el listado con ese filtro ya aplicado. Las cifras se actualizan solas cuando cambia algún incidente.

### Personalizar el resumen

En portátil y monitor, pulsa **Personalizar** para mover los widgets, cambiarles el tamaño, quitarlos o agregar otros. Pulsa **Guardar** para conservar el diseño o **Restaurar original** para volver al de fábrica. El diseño es personal: cada persona tiene el suyo, y se respeta en todos sus equipos. En tablet y celular los widgets se acomodan solos en el orden elegido.

## Exportar a Excel

En el listado, aplica los filtros que necesites y pulsa **Exportar** (en celular y tablet está en el menú **⋯**). Trazo confirma cuántas filas va a exportar y descarga un archivo CSV que Excel abre directamente, con acentos y fechas correctas. El límite es de 50.000 filas por archivo: si el filtro trae más, Trazo pide acotarlo. Cada exportación queda registrada en la auditoría.

## Eliminar y restaurar

Eliminar un incidente lo saca de los listados y del resumen, pero no lo borra. En **Más filtros → Ver eliminados** aparecen los eliminados, cada uno con su botón **Restaurar**.

## Auditoría de incidentes

En **Auditoría → Incidentes** aparece todo lo que pasó en todos los incidentes, incluidos los eliminados: quién hizo cada cambio, cuándo y desde qué dirección y navegador. Se puede filtrar por fechas (los últimos 30 días por defecto), por persona, por tipo de cambio o por un incidente puntual (`INC-000123`).

## Para profundizar

- [Ciclo de vida de un incidente](ciclo-de-vida.md)
- [Roles y permisos](roles.md)
