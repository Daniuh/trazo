---
title: "ADR-0058: Exportación en la práctica: CSV para Excel en español, tope de filas y registro en la auditoría"
description: "Exportación en la práctica: CSV para Excel en español, tope de filas y registro en la auditoría"
audience: tecnico
area: decisiones
order: 68
---

# ADR-0058: Exportación en la práctica: CSV para Excel en español, tope de filas y registro en la auditoría

- **Estado:** Aceptada
- **Fecha:** 2026-10-31

## Contexto
El [ADR-0036](0036-exportacion-csv.md) ya había fijado lo esencial: CSV generado por partes, UTF-8 con BOM, los mismos filtros del listado y un límite de frecuencia (5 por minuto, aprobado con la API). Al construirlo quedaban por decidir:

- Cómo se pide la exportación y cómo llega al navegador, si el token va en la cabecera.
- Si hay un tope de filas.
- Qué separador usar y qué columnas incluir.
- Cómo se protege el archivo contra la inyección de fórmulas.
- Si queda constancia de cada exportación, y dónde.
- Cómo se leen las filas sin cargar la memoria y sin perder el orden del listado.

## Alternativas evaluadas
Cómo se pide:
1. **Endpoint propio, `GET /incidents/export`, con los mismos parámetros del listado.**
2. Un parámetro `format=csv` en `GET /incidents`.

Cómo llega al navegador:
1. **El cliente HTTP la descarga como archivo (`blob`) y la app la guarda.**
2. Un enlace firmado de un solo uso.
3. El token en la URL de descarga.

Tamaño:
1. **Tope de 50.000 filas (`EXPORT_MAX_ROWS`), contado antes de empezar.**
2. Sin tope.
3. Exportaciones grandes en segundo plano, con aviso cuando el archivo esté listo.

Separador:
1. **Punto y coma.**
2. Coma.
3. Coma con una primera línea `sep=;`.

Registro:
1. **En `admin_actions`, con un tipo nuevo (`incidents_exported`), los filtros y el número de filas.**
2. Una tabla propia de exportaciones.
3. Solo en los logs del sistema.

Lectura:
1. **Por bloques de 1.000 filas con el mismo orden del listado (`lazy`).**
2. Por bloques ordenados por id (`lazyById`).
3. Un cursor de una sola consulta.

## Decisión
- **Endpoint propio.** Usa la misma validación (`ListIncidentsRequest`), la misma visibilidad (`VisibilityScope::forList`) y la misma consulta (`IncidentFilters`) del listado. Nadie exporta lo que no puede ver, y lo que sale coincide con lo que se ve. Exige `incidents.export`, y además `incidents.restore` para los eliminados. Límite propio: 5 por minuto por persona.
- **Descarga como archivo.** El front la pide con su token, igual que cualquier otra consulta, y la guarda con el nombre que propone el servidor (`incidentes-2026-10-25-1430.csv`). Si falla, el cliente HTTP lee el error que llega dentro del archivo y conserva el código de la API.
- **Tope de 50.000 filas.** Antes de escribir nada se cuentan. Si se pasa, la API responde 422 `EXPORT_TOO_LARGE` con el total y el máximo, y el front pide acotar los filtros. Medido en el Docker local: 50.000 filas son 7,9 MB y tardan 2,1 s, con la memoria del proceso estable en 28 MB.
- **Formato pensado para Excel en español:**
  - UTF-8 con BOM, para las tildes.
  - Punto y coma como separador.
  - Fechas en la hora del negocio (`BUSINESS_TIMEZONE`), con el desfase en el encabezado: "Creado (UTC-05:00)".
  - "Vencido" calculado con la misma regla de la API (`IncidentRules::isOverdue`).
- **Columnas:** código, título, estado, prioridad, creado por, asignado a, creado, vencimiento, resuelto y vencido. **Sin la descripción.**
- **Protección contra fórmulas.** Una celda que empiece por `=`, `+`, `-`, `@`, tabulador o salto de línea se guarda con un apóstrofo delante, y Excel la muestra como texto (recomendación de OWASP).
- **Registro en `admin_actions`.** Se anota al pedirla, con el número de filas y los filtros usados. Estados y prioridades van con código y nombre, como en el historial, y las personas por su ULID. Para escribir ahí desde Incidentes, el puerto `AdminLog` y su implementación pasaron de Users a Shared; la fase de sistema también los usará.
- **Lectura por bloques con el orden del listado.** Solo las columnas del archivo, con los nombres de personas por `JOIN` y los catálogos ya cargados en memoria.
- **En pantalla:** "Exportar" en la barra del listado, solo con el permiso. Pide confirmación con el número de incidentes. En portátil queda solo el ícono, porque con texto la barra no cabe en una fila; en celular y tablet va en el menú ⋯ de la barra.

## Alternativas descartadas
- **`format=csv` en el listado:** mezcla dos respuestas distintas, con permisos y límites distintos, en un mismo endpoint.
- **Enlace firmado:** obliga a guardar y vencer enlaces y agrega un endpoint sin sesión. Con el tope, el archivo cabe sin problema en la memoria del navegador.
- **Token en la URL:** quedaría en el historial del navegador y en los logs del gateway.
- **Sin tope:** la memoria del servidor aguanta, pero no el tiempo de la petición ni el del navegador. Además, una extracción sin límite es justo lo que conviene frenar.
- **En segundo plano:** necesita cola, almacenamiento de archivos, vencimiento y aviso. Queda como mejora para cuando haga falta superar el tope.
- **Coma:** el Excel configurado en español usa la coma como separador decimal y abre esos archivos en una sola columna.
- **`sep=;`:** solo lo entiende Excel; otras herramientas leen esa línea como un dato más.
- **Tabla propia:** repite lo que ya resuelve `admin_actions` y quedaría fuera de la pantalla de auditoría.
- **Solo logs:** los logs son para fallas y eventos técnicos; esto es la actividad de una persona.
- **`lazyById`:** obliga a ordenar por id y el archivo ya no seguiría el orden elegido en el listado.
- **Cursor de una consulta:** el driver de MySQL guarda en memoria todo el resultado antes de entregarlo.

## Consecuencias
- Lo exportado es siempre igual a lo que la persona ve filtrado. Una prueba lo compara con el listado para tres roles y tres órdenes.
- Cada exportación aparece en Auditoría → Administración con sus filtros y filas. Como no apunta a ninguna persona, la ve el Super admin y, si tiene acceso a ese registro, quien la hizo. Con la matriz actual, el Supervisor exporta pero no ve el registro de administración.
- Para más de 50.000 filas hay que exportar por partes (por ejemplo, por rangos de fechas) hasta construir la exportación en segundo plano.
- El `.xlsx` nativo sigue propuesto como mejora; el CSV abre en Excel, LibreOffice y Google Sheets.
- La exportación de la auditoría queda fuera: tiene muchas más filas y datos sensibles (IP y navegador). Queda propuesta como mejora.

## Dónde está en el código
- Back:
  - Caso de uso: [`ExportIncidents`](../../backend/app/Modules/Incidents/Application/ExportIncidents.php).
  - Consulta: [`EloquentIncidentExport`](../../backend/app/Modules/Incidents/Infrastructure/EloquentIncidentExport.php) e [`IncidentFilters`](../../backend/app/Modules/Incidents/Infrastructure/IncidentFilters.php).
  - Archivo: [`IncidentCsv`](../../backend/app/Modules/Incidents/Http/IncidentCsv.php) y [`CsvDownload`](../../backend/app/Modules/Shared/Http/CsvDownload.php).
  - Registro: [`AdminLog`](../../backend/app/Modules/Shared/Application/Ports/AdminLog.php).
- Front:
  - Estado y acciones del listado: [`useIncidentList`](../../frontend/src/modules/incidents/application/useIncidentList.ts) y [`listApi`](../../frontend/src/modules/incidents/infrastructure/listApi.ts).
  - Barra: [`IncidentToolbar.vue`](../../frontend/src/modules/incidents/presentation/list/IncidentToolbar.vue).
  - Descarga: [`saveFile.ts`](../../frontend/src/shared/download/saveFile.ts) y [`client.ts`](../../frontend/src/shared/http/client.ts).
- Pruebas: [`ExportIncidentsTest.php`](../../backend/tests/Feature/Incidents/ExportIncidentsTest.php) y [`e2e/export.spec.ts`](../../frontend/e2e/export.spec.ts).
