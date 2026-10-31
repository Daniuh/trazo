---
title: "ADR-0045: Listado en el front con los filtros en la URL y una presentación por vista"
description: "Listado en el front con los filtros en la URL y una presentación por vista"
audience: tecnico
area: decisiones
order: 55
---

# ADR-0045: Listado en el front con los filtros en la URL y una presentación por vista

- **Estado:** Aceptada
- **Fecha:** 2026-10-22

## Contexto
El listado combina búsqueda, cinco filtros principales, rangos de fechas, orden y paginación. Tiene que funcionar igual de bien en un celular de 320 px y en un monitor. Además, quien soporta a un cliente necesita poder compartir "lo que está viendo".

## Alternativas evaluadas
Dónde guardar los criterios:
1. **En la URL,** como única fuente: la vista lee la URL y cada cambio la reescribe.
2. **En un store de Pinia,** sincronizado con la URL en las dos direcciones.
3. **Solo en memoria** del componente.

Cómo presentar los filtros:
1. **La misma barra en todas las vistas,** que se acomoda con el ancho.
2. **Una presentación por vista:** selectores y "Más filtros" en portátil y monitor; un panel inferior con chips en celular y tablet.

Cómo mostrar el incidente elegido en pantallas grandes:
1. Abrir siempre el detalle.
2. **Vista previa lateral solo en monitor,** con las piezas del detalle; en portátil se abre el detalle.

## Decisión
- **Criterios en la URL como única fuente** (`listQuery.ts`). Un enlace o una recarga muestran lo mismo. Lo que no se reconoce se ignora y vuelve al valor por defecto, y cambiar un filtro vuelve a la página 1.
- **Una presentación por vista,** con la misma lógica debajo: `IncidentFilterFields` dibuja selectores o chips según la variante, e `IncidentMoreFilters` se reutiliza en el menú y en el panel.
- **El panel de celular y tablet se edita en borrador** y pide a la API cuántos resultados daría antes de aplicarlo. Así se evita recargar el listado con cada toque.
- **Vista previa lateral solo en monitor,** reutilizando las insignias, los datos y el cambio de estado del detalle (`useStatusChange`).
- **Las opciones de cada filtro salen de los permisos** (`useListFilterOptions`), pero el resultado siempre lo decide la API.

## Alternativas descartadas
- **Store sincronizado con la URL:** son dos fuentes que se pueden desincronizar (botón "atrás", enlace pegado) y no aportan nada que la URL no dé.
- **Solo en memoria:** se pierde al recargar y no se puede compartir.
- **La misma barra en todas las vistas:** en celular, cinco selectores ocupan la pantalla entera antes del primer resultado, y los selectores pequeños son difíciles de tocar.
- **Vista previa también en portátil:** a 1024 px la tabla y la vista previa juntas quedan apretadas, y habría que esconder demasiadas columnas.

## Consecuencias
- Cualquier filtro nuevo se agrega en tres puntos: el tipo (`listing.ts`), la URL (`listQuery.ts`) y los parámetros de la API (`listApi.ts`). Las pruebas de `listQuery.spec.ts` cubren la ida y la vuelta.
- Si llega una respuesta vieja después de una nueva, se descarta, para no mostrar resultados de un filtro anterior.
- Mientras cargan los catálogos, los filtros activos no muestran códigos internos: la etiqueta aparece cuando se conoce su nombre.
