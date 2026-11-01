---
title: Módulo de auditoría (frontend)
description: Pantalla de auditoría con una pestaña por registro, filtros en la URL, "Cargar más" y el detalle de cada registro con IP y navegador.
audience: tecnico
area: frontend
module: audit
order: 7
---

# Módulo de auditoría (frontend)

Muestra la auditoría global ([Módulo Audit](../backend/audit.md)). **Las reglas viven en la API:** qué pestañas existen sale de los permisos de la sesión, qué registros llegan lo decide el alcance del back, y qué enlaces se pueden abrir sale del campo `links` de cada registro. El front no calcula la jerarquía.

El menú muestra "Auditoría" con cualquiera de los dos permisos (`audit.incidents.view` o `audit.admin.view`). Una entrada del menú ahora admite una lista de permisos y aparece con uno de ellos.

## Pantallas

| Ruta | Pantalla |
|---|---|
| `/auditoria` | Lleva a la primera pestaña que la persona puede ver (incidentes, si no, administración) |
| `/auditoria/incidentes` | Historial de todos los incidentes |
| `/auditoria/administracion` | Registro de la administración |

Con las dos pestañas, arriba aparece el selector "Incidentes · Administración" (`AppSegmented`). Cambiar de pestaña empieza sin filtros. Elegir la pestaña de `/auditoria` va en un `beforeEnter`, no en un `redirect`: así corre después de la guarda que recupera la sesión y ya se conocen los permisos.

### Por vista

| Vista | Lista | Detalle |
|---|---|---|
| Monitor | Tabla (`AppDataTable`): fecha, hecho por, acción, sobre qué y el primer cambio | Al lado de la tabla; la columna "Cambio" se oculta mientras está abierto |
| Portátil | Tabla | Panel desde la derecha (`AppSheet`) |
| Tablet y celular | Tarjetas | Panel inferior (`AppSheet`) |

### Filtros

- **En la URL:** `from`, `to`, `actor`, `type`, y `incident` o `user` y `role`, según la pestaña. Un enlace o una recarga conservan la búsqueda.
- **Los campos:**
  - Fecha (`AppDateRangeField`, "Últimos 30 días" si está vacía).
  - Hecho por y Persona afectada: `AppSelect` con `filterable`, para escribir y encontrar entre muchas personas.
  - Acción (varias).
  - En incidentes, el código (se aplica al dejar de escribir).
  - En administración, el rol afectado.
- **En tablet y celular,** "Filtros (n)" abre el panel con los filtros en borrador. Ahí el código pasa al borrador de inmediato, para que tocar "Ver resultados" no lo pierda.
- **Debajo de los filtros,** el rango que la API aplicó ("Del 24 de sept al 24 de oct").
- **Un código de incidente mal escrito** muestra el mensaje junto al campo y, en el lugar de la lista, "Revisa el código del incidente" con "Quitar el código". En celular el campo queda dentro del panel, así que el aviso también se ve fuera.

### Lista y detalle

- **"Cargar más"** pide la siguiente tanda con el cursor y la agrega al final. Al terminar, se lee "No hay más registros en este rango".
- **El detalle** muestra:
  - Cuándo (fecha y hace cuánto).
  - Hecho por, con "Ver su actividad", que filtra por esa persona.
  - Sobre qué, con "Abrir incidente", "Abrir ficha" o "Abrir rol" si `links` lo permite.
  - "Qué cambió", campo por campo.
  - "Desde dónde": IP y navegador. Los datos de demostración no traen IP y se lee "Sin registro (datos de demostración)".
- **Los textos** salen de `auditTexts.ts`:
  - Cada campo se escribe como "valor anterior → valor nuevo".
  - Roles y permisos se muestran por su nombre; si ya no existen, por su código.
  - La política de MFA usa el mismo texto de la pantalla de roles.
  - Una exportación de incidentes dice "Sobre qué: Incidentes" y muestra las filas exportadas y cada filtro usado (búsqueda, estado, prioridad, personas, rangos de fechas, solo vencidos, eliminados). Sin filtros, lo aclara: "Ninguno: todo lo que la persona podía ver".

### Desde otras pantallas

La ficha de una persona tiene "Ver su actividad" (`auditLocation`). Lleva a la administración filtrada por esa persona; si quien mira solo tiene la auditoría de incidentes, a esa.

## Capas

| Capa | Pieza | Qué hace |
|---|---|---|
| domain | `audit.ts` | Registros de cada pestaña, catálogo, criterios y cuántos filtros hay activos |
| infrastructure | `auditApi.ts` | `/audit/catalog`, `/audit/incidents` y `/audit/admin`, y la traducción de la respuesta |
| application | `auditQuery`, `useAudit` (`useAuditList`, `useAuditCatalog`), `auditLink` | Criterios en la URL, lista por cursor y catálogo, y enlace filtrado desde otras pantallas |
| presentation | `AuditPage`, `list/AuditToolbar`, `list/AuditFilterFields`, `list/AuditTable`, `list/AuditCards`, `list/AuditDetail` | Pantalla, filtros, tabla, tarjetas y detalle |
| presentation | `auditTexts.ts` | Sobre qué fue y qué cambió, en texto claro |
| — | `errors.ts`, `routes.ts`, `navigation.ts` | Mensajes, rutas y entrada del menú |

Piezas compartidas nuevas:

- `useCursorList` (`shared/listing`): listado por cursor ligado a la URL. El cursor no va en la URL, así que una recarga vuelve al principio.
- `AppSheet` (`shared/ui`): panel desde la derecha o desde abajo. Es ahora la base de `AppFilterSheet`.
- La opción `filterable` de `AppSelect`.

## Dónde está en el código

- [`frontend/src/modules/audit/`](../../frontend/src/modules/audit/)
- Compartido: [`useCursorList`](../../frontend/src/shared/listing/useCursorList.ts), [`AppSheet`](../../frontend/src/shared/ui/AppSheet.vue) y [`AppSelect`](../../frontend/src/shared/ui/form/AppSelect.vue)
- Pruebas: [`audit.spec.ts`](../../frontend/src/modules/audit/audit.spec.ts) y [`e2e/audit.spec.ts`](../../frontend/e2e/audit.spec.ts)
- Decisión: [ADR-0056](../decisiones/0056-auditoria-global.md)
