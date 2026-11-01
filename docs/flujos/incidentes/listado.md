---
title: 'Listado: filtros, búsqueda y grupos'
description: Cómo se arma el listado de incidentes, desde los criterios en la dirección hasta la consulta con la visibilidad de cada persona, la búsqueda y los grupos.
audience: tecnico
area: flujos
module: incidents
order: 8
---

# Listado: filtros, búsqueda y grupos

Los criterios del listado viven solo en la dirección (`?estado=…&agrupar=…`): se pueden compartir, el botón Atrás funciona y una recarga muestra lo mismo. La API aplica a cada persona la misma regla de visibilidad que el detalle, traducida a SQL, así que nadie ve en la lista algo que no podría abrir.

```flujo
carriles: [Persona, Navegador, API, Base de datos]
pasos:
  - id: filtra
    carril: Persona
    tipo: inicio
    texto: Filtra, busca, agrupa u ordena
    detalle: 'Estado, prioridad, asignado (o sin asignar), creador, rangos de creación y de vencimiento, vencidos, reapertura pendiente y, con permiso, ver eliminados. En celular y tablet los filtros van en un panel en borrador que dice cuántos saldrían antes de aplicar.'
    va: url
  - id: url
    carril: Navegador
    texto: Escribe los criterios en la dirección
    detalle: La búsqueda se envía desde 3 letras. Un filtro nuevo solo toca `listing.ts`, `listQuery.ts` y `listApi.ts`.
    va: pide
  - id: pide
    carril: Navegador
    texto: Pide GET /incidents con los parámetros
    detalle: '`page`, `per_page` (máximo 100), `sort` de lista blanca, `search`, `group_by` y `filter[...]`.'
    va: valida
  - id: valida
    carril: API
    tipo: decision
    texto: ¿Parámetros válidos?
    detalle: Una búsqueda necesita al menos una palabra de 3 letras o un código (`TZ-26-0142` o `26-142`).
    si: eliminados
    no: e422
  - id: e422
    carril: API
    tipo: error
    texto: Responde 422
  - id: eliminados
    carril: API
    tipo: decision
    texto: Si pide eliminados, ¿tiene incidents.restore?
    si: alcance
    no: e403
  - id: e403
    carril: API
    tipo: error
    texto: Responde 403
  - id: alcance
    carril: API
    texto: Traduce la visibilidad a SQL
    detalle: 'Todos (`incidents.view.any`), lo propio, lo asignado y lo sin asignar, según los permisos. Es la misma regla del detalle (`VisibilityScope`) y una prueba compara ambas persona por persona.'
    va: busca
  - id: busca
    carril: Base de datos
    texto: Busca con FULLTEXT o por código
    detalle: 'Búsqueda booleana con prefijos ("impre" encuentra "impresora") sobre título y descripción; los operadores que alguien intente colar se quitan. Un código se busca exacto con su año.'
    va: grupos
  - id: grupos
    carril: Base de datos
    tipo: decision
    texto: ¿Agrupado?
    detalle: 'Por defecto la lista se agrupa por vencimiento: Vencidos, Vencen hoy, Próximos 7 días, Más adelante y Terminados. También por estado, prioridad, asignado o sin grupos.'
    si: cuenta
    no: pagina
  - id: cuenta
    carril: Base de datos
    texto: Cuenta cada grupo y salta los anteriores
    detalle: Cuenta cada grupo con índices, sin cruzar tablas; salta los grupos que quedan antes de la página y pide solo los ids de los que la tocan. Con 100.000 incidentes, entre 22 y 42 ms.
    va: pagina
  - id: pagina
    carril: Base de datos
    texto: Trae la página con desempate por id
    detalle: 'Orden de lista blanca y `id` como desempate, para que dos filas iguales no se repitan ni se salten entre páginas. Carga todo lo de la página de una vez (sin N+1).'
    va: responde
  - id: responde
    carril: API
    texto: Responde data + meta con actions
    detalle: 'Cada incidente trae `actions` (lo que esta persona puede hacer), `is_overdue` y `deadline` calculados en el back; `meta` trae el total y, si hay grupos, el total real de cada uno.'
    va: muestra
  - id: muestra
    carril: Navegador
    tipo: fin
    texto: Muestra la lista agrupada o la tabla
    detalle: 'Lista por defecto; `?vista=tabla` cambia a tabla en portátil y monitor (con vista previa lateral en monitor) y tarjetas en tablet y celular. Si otras personas cambian incidentes, aparece "Hay N incidentes con cambios de otras personas · Actualizar" sin mover las filas.'
```

## Qué ve la persona en cada error

| Código | HTTP | Cuándo | Mensaje en pantalla |
|---|---|---|---|
| `SEARCH_TOO_SHORT` | 422 | La búsqueda no tiene una palabra de 3 letras ni un código | No tiene mensaje en pantalla: el navegador solo envía la búsqueda desde 3 letras, así que este error solo lo recibe quien llama a la API directamente. |
| `FORBIDDEN_ACTION` | 403 | Pide eliminados sin poder restaurar | No tienes permiso para hacer esta acción. Si crees que deberías tenerlo, habla con un administrador. |

Una lista sin resultados no es un error: muestra el estado vacío con la opción de quitar los filtros.

## Dónde está en el código

- [`ListIncidents`](../../../backend/app/Modules/Incidents/Application/ListIncidents.php) y [`ListIncidentsRequest`](../../../backend/app/Modules/Incidents/Http/Requests/ListIncidentsRequest.php).
- [`VisibilityScope`](../../../backend/app/Modules/Incidents/Domain/VisibilityScope.php) y [`VisibilitySql`](../../../backend/app/Modules/Incidents/Infrastructure/VisibilitySql.php): una regla, dos formas.
- [`EloquentIncidentList`](../../../backend/app/Modules/Incidents/Infrastructure/EloquentIncidentList.php), [`IncidentFilters`](../../../backend/app/Modules/Incidents/Infrastructure/IncidentFilters.php) e [`IncidentGroups`](../../../backend/app/Modules/Incidents/Infrastructure/IncidentGroups.php).
- [`SearchText`](../../../backend/app/Modules/Incidents/Http/Rules/SearchText.php): mínimo de la búsqueda.
- [`listQuery`](../../../frontend/src/modules/incidents/application/listQuery.ts), [`useIncidentList`](../../../frontend/src/modules/incidents/application/useIncidentList.ts) e [`IncidentListPage`](../../../frontend/src/modules/incidents/presentation/IncidentListPage.vue).
- Explicación del módulo: [Módulo Incidents](../../backend/incidents.md).
