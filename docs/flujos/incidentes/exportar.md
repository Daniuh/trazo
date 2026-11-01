---
title: Exportar a CSV
description: Exportar exactamente lo filtrado en el listado, con su tope, su registro en la auditoría y la protección contra fórmulas en Excel.
audience: tecnico
area: flujos
module: incidents
order: 9
---

# Exportar a CSV

"Exportar" descarga los incidentes del listado tal como están filtrados y ordenados. Usa los mismos parámetros, la misma validación, la misma visibilidad y la misma consulta del listado, así que el archivo nunca trae algo que la persona no podría ver en pantalla.

```flujo
carriles: [Persona, Navegador, API, Base de datos]
pasos:
  - id: pulsa
    carril: Persona
    tipo: inicio
    texto: Pulsa Exportar
    detalle: Monitor con texto; portátil solo el ícono; celular y tablet en el menú "Más opciones del listado".
    va: confirma
  - id: confirma
    carril: Navegador
    texto: Pide confirmar cuántos saldrán
    detalle: '"Se exportarán N con los filtros y el orden actuales, en un archivo CSV que se abre con Excel."'
    va: pide
  - id: pide
    carril: Navegador
    texto: Pide GET /incidents/export con los filtros
    va: limite
  - id: limite
    carril: API
    tipo: decision
    texto: ¿Menos de 5 exportaciones en el minuto?
    si: permiso
    no: e429
  - id: e429
    carril: API
    tipo: error
    texto: Responde 429
  - id: permiso
    carril: API
    tipo: decision
    texto: ¿Tiene incidents.export (y restore si pide eliminados)?
    si: cuenta
    no: e403
  - id: e403
    carril: API
    tipo: error
    texto: Responde 403
  - id: cuenta
    carril: Base de datos
    tipo: decision
    texto: ¿Son 50.000 filas o menos?
    detalle: 'Cuenta antes de leer. El tope es `EXPORT_MAX_ROWS` (50.000 por defecto).'
    si: registra
    no: e422
  - id: e422
    carril: Base de datos
    tipo: error
    texto: Responde 422 con total y tope
  - id: registra
    carril: Base de datos
    texto: Registra la exportación en la auditoría
    detalle: 'Antes de enviar el archivo, porque una descarga cortada a mitad igual dejó salir datos. En `admin_actions`, tipo `incidents_exported`, con el número de filas y los filtros (estados y prioridades con código y nombre, personas por ULID).'
    va: lee
  - id: lee
    carril: Base de datos
    texto: Lee por tandas de 1.000
    detalle: 'Con el orden del listado. Medido: 50.000 filas son 7,9 MB en 2,1 s y la memoria se mantiene en unos 28 MB.'
    va: escribe
  - id: escribe
    carril: API
    texto: Escribe el CSV mientras lee
    detalle: 'Con BOM y `;` para que Excel lo abra bien en español; fechas en la zona del negocio con el desfase en el encabezado; "Vencido" calculado. Una celda que empieza por `=`, `+`, `-`, `@`, tabulación o salto de línea lleva un apóstrofo delante, para que Excel no la ejecute como fórmula. Sin caché (`no-store`).'
    va: guarda
  - id: guarda
    carril: Navegador
    tipo: fin
    texto: Guarda incidentes-AAAA-MM-DD-HHMM.csv
```

## Qué ve la persona en cada error

| Código | HTTP | Cuándo | Mensaje en pantalla |
|---|---|---|---|
| `EXPORT_TOO_LARGE` | 422 | Más de 50.000 incidentes con esos filtros | Con estos filtros salen {total} incidentes y el máximo por archivo es {max}. Acota los filtros, por ejemplo con un rango de fechas, y vuelve a exportar. |
| `TOO_MANY_REQUESTS` | 429 | Más de 5 exportaciones en un minuto | Hiciste demasiados intentos seguidos. Espera {wait} y vuelve a intentarlo. |
| `FORBIDDEN_ACTION` | 403 | Sin permiso de exportar | No tienes permiso para hacer esta acción. Si crees que deberías tenerlo, habla con un administrador. |

Como la descarga llega como archivo, el cliente HTTP lee el error dentro del `blob` para poder mostrar el mensaje correcto.

## Dónde está en el código

- [`ExportIncidents`](../../../backend/app/Modules/Incidents/Application/ExportIncidents.php): permiso, tope y registro.
- [`EloquentIncidentExport`](../../../backend/app/Modules/Incidents/Infrastructure/EloquentIncidentExport.php) e [`IncidentCsv`](../../../backend/app/Modules/Incidents/Http/IncidentCsv.php): lectura por tandas y columnas.
- [`CsvDownload`](../../../backend/app/Modules/Shared/Http/CsvDownload.php): BOM, separador y protección de fórmulas.
- [`useIncidentList`](../../../frontend/src/modules/incidents/application/useIncidentList.ts) (`exportCsv`) y [`saveFile`](../../../frontend/src/shared/download/saveFile.ts).
- Explicación del módulo: [Módulo Incidents](../../backend/incidents.md).
