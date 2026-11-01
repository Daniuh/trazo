---
title: Personalizar el dashboard
description: Mover, estirar, quitar y agregar widgets en un borrador, guardarlo por persona en el servidor o volver al diseño original.
audience: tecnico
area: flujos
module: dashboard
order: 2
---

# Personalizar el dashboard

En portátil y monitor, el botón "Personalizar" convierte el dashboard en un editor sobre una grilla de 12 columnas. Todo se hace en un borrador: nada cambia en el servidor hasta pulsar Guardar. En tablet y celular no se edita; los widgets se acomodan solos en el orden guardado, y un ícono de información junto al título lo explica.

```flujo
carriles: [Persona, Navegador, API, Base de datos]
pasos:
  - id: entra
    carril: Persona
    tipo: inicio
    texto: Pulsa Personalizar
    detalle: Aparece una barra fija con Agregar widget, Restaurar original, Cancelar y Guardar. Los widgets siguen mostrando datos reales, pero sin poder pulsarlos (`inert`).
    va: edita
  - id: edita
    carril: Persona
    texto: Mueve, estira, quita o agrega widgets
    detalle: Arrastrando por la agarradera o desde el menú ⋯ de cada widget (también con teclado). La medida (columnas × filas) aparece solo mientras se estira.
    va: reglas
  - id: reglas
    carril: Navegador
    texto: Aplica las reglas al borrador
    detalle: 'Reglas puras en `layoutEditing.ts`: mover intercambia con el vecino, estirar respeta los topes de cada widget, todo se compacta hacia arriba y un widget agregado va al final con su tamaño original. Las cifras siguen al borrador.'
    va: decide
  - id: decide
    carril: Persona
    tipo: decision
    texto: ¿Qué hace al terminar?
    va:
      - { a: guarda, etiqueta: Guardar }
      - { a: restaura, etiqueta: Restaurar }
      - { a: cancela, etiqueta: Cancelar }
  - id: cancela
    carril: Navegador
    tipo: fin
    texto: Descarta el borrador
    detalle: Si hubo cambios, pide confirmación. Salir de la página o cerrar la pestaña con cambios también la pide.
  - id: restaura
    carril: Navegador
    texto: Confirma y envía DELETE /dashboard/layout
    va: borra
  - id: borra
    carril: Base de datos
    texto: Borra las filas de la persona
    detalle: Sin filas, la persona ve el diseño original del catálogo (`in_default_layout`).
    va: original
  - id: original
    carril: Navegador
    tipo: fin
    texto: Muestra el diseño original
  - id: guarda
    carril: Navegador
    texto: Envía PUT /dashboard/layout
    detalle: Con cada widget, su lugar (x, y, ancho, alto) y si está visible.
    va: valida
  - id: valida
    carril: API
    tipo: decision
    texto: ¿Diseño válido para su rol?
    detalle: 'Cada widget permitido para su rol, sin repetidos, dentro de las 12 columnas, con su tamaño entre los topes y sin encimarse con otro.'
    si: reemplaza
    no: e422
  - id: e422
    carril: API
    tipo: error
    texto: Responde 422 con el widget
  - id: reemplaza
    carril: Base de datos
    texto: Reemplaza su diseño
    detalle: 'Una fila por widget en `user_dashboard_widgets` (clave compuesta persona + widget). Un widget oculto conserva su lugar para cuando se vuelva a agregar.'
    va: listo
  - id: listo
    carril: Navegador
    tipo: fin
    texto: Sale del modo edición con el diseño nuevo
```

Si la ventana se angosta por debajo del tamaño de portátil mientras se edita, aparece un aviso y el borrador se conserva hasta que vuelva a caber.

## Qué ve la persona en cada error

| Código | HTTP | Cuándo | Mensaje en pantalla |
|---|---|---|---|
| `WIDGET_NOT_AVAILABLE` | 422 | El diseño trae un widget que su cuenta ya no puede usar | Uno de los widgets ya no está disponible para tu cuenta. Recarga la página para ver tu dashboard actual. |
| `WIDGET_DUPLICATED` | 422 | Un widget aparece dos veces | Un widget quedó repetido en el diseño. Recarga la página y vuelve a acomodarlo. |
| `WIDGET_OUT_OF_GRID` | 422 | Un widget se sale de las 12 columnas | Un widget quedó fuera del espacio del dashboard. Muévelo dentro de la grilla e inténtalo de nuevo. |
| `WIDGET_SIZE_OUT_OF_RANGE` | 422 | Un widget pasa de sus topes | Un widget quedó más grande o más pequeño de lo permitido. Ajusta su tamaño e inténtalo de nuevo. |
| `WIDGETS_OVERLAP` | 422 | Dos widgets se enciman | Dos widgets quedaron uno encima del otro. Sepáralos e inténtalo de nuevo. |

Con el editor del navegador estos errores no deberían ocurrir: la API los revisa porque es la única garantía.

## Dónde está en el código

- [`ManageDashboardLayout`](../../../backend/app/Modules/Dashboard/Application/ManageDashboardLayout.php): ver, guardar y restaurar.
- [`DashboardGrid`](../../../backend/app/Modules/Dashboard/Domain/DashboardGrid.php): validación de la grilla y diseño original.
- [`DatabaseLayoutStore`](../../../backend/app/Modules/Dashboard/Infrastructure/DatabaseLayoutStore.php): filas por persona.
- [`useDashboardEditor`](../../../frontend/src/modules/dashboard/application/useDashboardEditor.ts) y [`layoutEditing.ts`](../../../frontend/src/modules/dashboard/domain/layoutEditing.ts).
- Explicación del módulo: [Módulo Dashboard](../../backend/dashboard.md).
