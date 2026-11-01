---
title: Componentes base
description: Componentes reutilizables de Trazo, cuándo usar cada uno y sus reglas.
audience: tecnico
area: frontend
module: design
order: 12
---

# Componentes base

Viven en `src/shared/ui/` y son los únicos que tocan Quasar directamente. Las pantallas los usan sin conocer la librería. En desarrollo, la **guía visual** (`/guia-visual`) los muestra todos con sus estados, para revisarlos en las cuatro vistas.

| Componente | Para qué |
|---|---|
| `AppButton` | Acciones. Variantes `primary`, `secondary`, `danger` y `link`; con `loading` queda bloqueado y lo anuncia (`aria-busy`). Alto táctil de 44 px en celular y tablet. |
| `AppIcon` | Íconos propios (ver [Íconos](iconos.md)). |
| `AppInfoTip` | Ícono de información con una ayuda oculta. Ver la regla abajo. |
| `AppBadge` | Etiqueta sólida para estados. Recibe un tono; el contraste ya viene resuelto. |
| `AppDot` | Punto de color con texto, para prioridades. Distinto a propósito de la etiqueta del estado. |
| `AppCard` | Superficie con título y acciones opcionales. |
| `AppTextField` · `AppSelect` | Campos con etiqueta arriba, ayuda en el ícono de información y error siempre visible debajo. Comparten la envoltura `AppField`. Con `type="password"`, el campo trae un botón para mostrar u ocultar la contraseña; `textarea` empieza con espacio para varias líneas; `inputmode="numeric"` abre el teclado numérico en el celular. `AppSelect` muestra su texto de ejemplo (en gris) cuando no hay valor, y no muestra el código interno del valor mientras sus opciones no han llegado. Con `filterable` se escribe para filtrar las opciones, sin importar tildes ni mayúsculas (listas largas, como las personas). |
| `AppDateTimeField` | Fecha y hora con el diseño de Trazo, no el del navegador. El campo cerrado se ve como un selector ("25 de oct, 10:30 a. m."). En portátil y monitor abre un menú con el calendario y la lista de horas lado a lado; en celular y tablet, un panel que sube desde abajo, con las horas en una fila deslizable. Horas cada 30 minutos; los días y horas pasados no se pueden elegir. Si recibe `clearLabel`, el pie ofrece vaciar el campo (p. ej. "Usar la sugerida"). El valor es ISO en UTC o `null`. La lógica vive aparte, en `dateTimeParts.ts`, con sus pruebas. |
| `AppPopupField` | Base de los campos que abren un panel: se ve como un selector y abre un menú bajo el campo en portátil y monitor, o un panel inferior de borde a borde en celular y tablet. Lo usan `AppDateTimeField` y `AppDateRangeField`. |
| `AppDateRangeField` | Rango de fechas sin hora, para filtros ("1 de oct – 22 de oct"). Toma días completos en la hora local: desde el inicio del primer día hasta el final del último. La lógica vive en `dateRange.ts`, con sus pruebas. |
| `AppCheckbox` | Casilla con el estilo de Trazo y alto táctil en celular y tablet. Con `disabled` queda visible pero sin efecto. |
| `AppRadioList` | Una opción entre varias, cada una con su nombre en negrita y una línea de descripción debajo (p. ej. el rol de una persona). Cada fila ocupa todo el ancho y se elige tocándola entera; la elegida lleva borde azul. Grupo con `legend` y error asociado. |
| `AppCodeField` | Código numérico de un solo uso (6 dígitos por defecto) en un solo campo: teclado numérico, `autocomplete="one-time-code"`, solo acepta dígitos (pegar "123 456" funciona) y los muestra separados. |
| `AppQrCode` | Código QR dibujado como SVG desde la matriz de la librería `qrcode`, sin `v-html` y con los colores de los tokens. Imagen con nombre para lectores de pantalla. |
| `AppCopyBlock` | Texto para copiar (una clave, un código) con el botón "Copiar", que cambia a "Copiado" y lo anuncia. |
| `AppChoiceChips` | Selección múltiple como chips que se activan y desactivan, cómoda para tocar. Activo: fondo azul y texto blanco. |
| `AppActionMenu` | Menú de opciones que se abre desde el botón que lo contiene. Cada opción puede llevar ícono, marcarse como peligrosa, quedar desactivada (visible pero sin efecto) o separarse de las anteriores con una línea; se cierra al elegir. El tipo de las opciones está en `menu.ts`. |
| `AppSegmented` | Una opción entre pocas, como botones unidos ("7 días · 30 días · 90 días"). Se anuncia como grupo de opciones (`radiogroup`); la activa va en azul con texto blanco. |
| `AppPagination` | "1–20 de 534", páginas con saltos (…) y tamaño de página. En celular, solo anterior, "Página 3 de 27" y siguiente. La página actual se anuncia con `aria-current`. |
| `AppDataTable` | Tabla de los listados en portátil y monitor. Las columnas se declaran como datos (`dataTable.ts`): las que ordenan anuncian el orden con `aria-sort`; `emphasis` da texto secundario o corta el texto largo con "…"; `hiddenLabel` deja el nombre solo para lectores de pantalla. Cada celda se dibuja con el slot `cell-<columna>`. Las filas se abren con clic o Enter, y la elegida se resalta. La usan incidentes y usuarios. |
| `AppSheet` | Panel sobre la página: desde la derecha en portátil y monitor, desde abajo en celular y tablet (o siempre desde abajo con `side="bottom"`). Título, botón de cerrar, contenido que se desplaza y un pie opcional fijo. |
| `AppFilterSheet` | Panel de filtros de celular y tablet, hecho sobre `AppSheet`: sube desde abajo, con los campos en el slot y un pie fijo con "Limpiar" y el botón de aplicar. |
| Gráficos | `AppBarList`, `AppDonutChart` y `AppColumnChart`. Ver [Gráficos](graficos.md). |
| `AppAlert` | Aviso siempre visible dentro de la página (error de un formulario o información imprescindible). Tonos `danger` (rol `alert`) e `info` (rol `status`); fondo de superficie con borde de color, nunca fondo tintado. |
| `AppSkeleton` · `AppEmptyState` · `AppErrorState` | Estados de carga, vacío y error. El de error usa el catálogo de mensajes y ofrece "Reintentar". |
| `AppFormDialog` | Diálogo con un formulario corto: título, explicación opcional, error general, los campos en el slot y Cancelar más el botón de guardar (en rojo si la acción es delicada). No se cierra solo: quien lo usa lo cierra cuando el guardado sale bien, así los errores quedan a la vista. Envuelve el formulario en un `div`, porque Quasar solo deja hacer clic en el primer `div` del diálogo. |
| `AppCodeDialog` | Sobre `AppFormDialog`: pide el código actual de la aplicación antes de una acción sensible (regenerar códigos, desactivar el MFA, confirmar un cambio de administración) y muestra el error de la API sin cerrarse. |
| `useFeedback()` | `confirm()` abre el diálogo de confirmación (devuelve `true` o `false`); `notify()` y `notifyError()` muestran notificaciones. `notify()` admite una acción (p. ej. "Abrir"); con ella el aviso dura 8 s en lugar de 4. Los errores no se cierran solos. |
| `useLeaveGuard(dirty, mensaje)` | Avisa antes de salir con cambios sin guardar: al cambiar de sección pide confirmación con el estilo de Trazo; al cerrar o recargar la pestaña, el navegador muestra su propio aviso. |
| `useViewport()` | Vista actual (`mobile`, `tablet`, `laptop` o `desktop`) y atajos como `isMobile`, `isLaptopUp` e `isDesktop`. |

## Ícono de información

Toda ayuda o aclaración que no sea imprescindible va en `AppInfoTip`, para mantener las pantallas limpias:

- Se muestra al pasar el cursor, al enfocarlo con el teclado o al tocarlo en el celular; se cierra al salir, con Escape o al tocar fuera.
- La burbuja calcula su posición para no salirse nunca de la pantalla.
- Es accesible: el botón tiene nombre propio y la ayuda está asociada con `aria-describedby`.

**Nunca** se usa para errores, mensajes de validación ni instrucciones necesarias para completar una acción: eso siempre queda visible.

## Reglas comunes

- Estilos en un solo archivo por componente, *mobile first*, con bloques `tablet`, `laptop` y `desktop`.
- Clases en BEM (`bloque__elemento--modificador`), verificado por Stylelint.
- Fondo de color siempre con texto negro o blanco según contraste.
- Comentarios: encabezado breve en cada archivo y comentarios cortos solo donde aportan.

## Dónde está en el código

- [`frontend/src/shared/ui/`](../../../frontend/src/shared/ui/)
- Guía visual: [`frontend/src/app/pages/DesignGuidePage.vue`](../../../frontend/src/app/pages/DesignGuidePage.vue)
- Pruebas: [`frontend/src/shared/ui/components.spec.ts`](../../../frontend/src/shared/ui/components.spec.ts)
