---
title: "ADR-0074: Código del incidente por año"
description: "El código pasa de INC-000123 (sacado del id) a prefijo, año y consecutivo del año: TZ-26-0142"
audience: tecnico
area: decisiones
order: 84
---

# ADR-0074: Código del incidente por año

- **Estado:** Aceptada. Reemplaza el formato `INC-000123` del [ADR-0010](0010-ulid.md); el ULID público no cambia.
- **Fecha:** 2026-10-31

## Contexto
El código legible salía del id interno: `INC-000123`. Funcionaba, pero tenía tres problemas:

- Seis cifras con ceros a la izquierda cuestan al dictarlo por teléfono ("inc, cero, cero, cero, uno…").
- No dice nada del incidente: uno de hace dos años y uno de ayer se ven iguales.
- Con el volumen, el número crece sin límite, y con más de un millón el formato cambia de largo.

El código es lo que las personas usan para referirse a un caso en un correo, un chat o una llamada, así que debe ser corto, fácil de dictar y con algo de contexto.

## Alternativas evaluadas
- **A. Prefijo + año + consecutivo del año:** `TZ-26-0142`.
- **B. Fecha + consecutivo del día:** `261028-07`.
- **C. Código corto aleatorio:** `K7QM-4X`.
- **D. Número simple:** `#142`.
- **E. Prefijo según tipo o prioridad:** `ALT-0142`.

## Decisión
- El código es **prefijo, año en dos cifras y consecutivo del año con al menos cuatro cifras**: `TZ-26-0142`. Desde el 10.000 el número sigue con más cifras (`TZ-26-10000`) sin romper nada.
- El prefijo sale de `INCIDENT_CODE_PREFIX` (por defecto `TZ`, de Trazo). Se define antes de salir a producción: cambiarlo después exige migrar los códigos.
- El año es el de la zona horaria del negocio (`BUSINESS_TIMEZONE`), igual que los días del dashboard.
- El número sale de un contador por año, `incident_code_sequences`. `IncidentCodes::next` lo toma con una sola sentencia (`INSERT … ON DUPLICATE KEY UPDATE` con `LAST_INSERT_ID`) dentro de la transacción que crea el incidente. La fila queda bloqueada hasta el final, así que dos creaciones a la vez nunca reciben el mismo número; si la creación falla, el contador también vuelve atrás.
- Búsqueda y filtro de auditoría reconocen el código con su año, con o sin prefijo, guiones y ceros: `TZ-26-0142`, `tz26-142`, `26-142`. Un número suelto (`142`) no se toma como código, porque se repite cada año y porque "142" también puede ser parte de un texto: se busca como texto.
- Los códigos existentes se recalculan una vez (migración 000039): cada año se numera en el orden en que se crearon sus incidentes, incluidos los eliminados, y el contador queda al día. Ningún otro registro guarda el código (el historial y la auditoría lo leen del incidente), así que no hay copias viejas que corregir.
- El ULID sigue siendo el identificador público de la API y de las URL. El código es solo para las personas.

## Alternativas descartadas
- **B (fecha + día):** largo de dictar y fácil de confundir con una fecha. El día exacto ya se ve en el detalle.
- **C (aleatorio):** no revela cuántos incidentes hay, pero no se ordena, no se recuerda y mezcla letras parecidas a números. Ese dato de volumen solo lo ven personas con sesión y no justifica perder legibilidad.
- **D (`#142`):** choca con cualquier otro número ("pedido #142") y en un correo no se reconoce como un incidente.
- **E (por prioridad):** la prioridad cambia y el código no debe cambiar nunca; quedaría desactualizado.
- **Seguir sacando el número del id:** el id no se reinicia por año y además tiene huecos (MySQL gasta autoincrementales en inserciones fallidas), así que no sirve como consecutivo.

## Consecuencias
- El código se dicta en pocos segundos ("te zeta, veintiséis, ciento cuarenta y dos") y dice de qué año es.
- Hay una tabla más, de una fila por año, y una espera mínima si dos personas crean a la vez, que no se nota en la práctica.
- Quien tenga anotado un código viejo (`INC-000123`) ya no lo encuentra por ese texto. Es aceptable porque el cambio se hace antes de la publicación; después, el formato no debería volver a cambiar.

## Dónde está en el código
- Formato y lectura: [`IncidentCode.php`](../../backend/app/Modules/Incidents/Domain/IncidentCode.php).
- Contador: [`IncidentCodes.php`](../../backend/app/Modules/Incidents/Infrastructure/IncidentCodes.php), usado por [`EloquentIncidentRepository.php`](../../backend/app/Modules/Incidents/Infrastructure/EloquentIncidentRepository.php) y por [`IncidentVolumeSeeder.php`](../../backend/database/seeders/IncidentVolumeSeeder.php).
- Migraciones: [`000038`](../../backend/database/migrations/2026_10_28_000038_create_incident_code_sequences_table.php) y [`000039`](../../backend/database/migrations/2026_10_28_000039_renumber_incident_codes.php).
- Búsqueda: [`IncidentFilters.php`](../../backend/app/Modules/Incidents/Infrastructure/IncidentFilters.php), [`SearchText.php`](../../backend/app/Modules/Incidents/Http/Rules/SearchText.php) y [`listing.ts`](../../frontend/src/modules/incidents/domain/listing.ts). Auditoría: [`IncidentCode.php`](../../backend/app/Modules/Audit/Http/Rules/IncidentCode.php).
- Pruebas: [`IncidentCodeTest.php`](../../backend/tests/Unit/Incidents/IncidentCodeTest.php) e [`IncidentCodesTest.php`](../../backend/tests/Feature/Incidents/IncidentCodesTest.php).
