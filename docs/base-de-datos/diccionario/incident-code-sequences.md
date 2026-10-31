---
title: incident_code_sequences
description: Contador por año de los códigos de incidente (TZ-26-0142).
audience: tecnico
area: base-de-datos
module: incidents
order: 22
---

# incident_code_sequences

Una fila por año con el último número de código entregado. `TZ-26-0142` es el incidente 142 de 2026; el siguiente que se cree ese año será el 143. El año es el de la zona horaria del negocio (`BUSINESS_TIMEZONE`), así que un incidente creado el 31 de diciembre a las 11 p. m. en Bogotá cuenta para ese año aunque en UTC ya sea el siguiente ([ADR-0074](../../decisiones/0074-codigo-de-incidente-por-ano.md)).

| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `year` | SMALLINT UNSIGNED | PK | Año completo (2026) |
| `last_number` | INT UNSIGNED | NOT NULL | Último número entregado en ese año |

## Cómo se toma un número

Al crear un incidente, una sola sentencia crea la fila del año o le suma uno, y deja el número en `LAST_INSERT_ID()` de esa conexión:

```sql
INSERT INTO incident_code_sequences (year, last_number) VALUES (?, LAST_INSERT_ID(1))
ON DUPLICATE KEY UPDATE last_number = LAST_INSERT_ID(last_number + 1)
```

La fila queda bloqueada hasta que termina la transacción que crea el incidente. Si dos personas crean a la vez, la segunda espera unos milisegundos y recibe el número siguiente: nunca hay dos iguales, y la restricción única de `incidents.code` lo respalda. Si la creación falla y la transacción se deshace, el contador también vuelve atrás, así que no quedan huecos por errores.

## Sin claves foráneas

No referencia a ninguna tabla ni es referenciada: el año no es una entidad, y el código ya queda guardado en `incidents.code`. Por la misma razón no tiene marcas de tiempo.

## Dónde está en el código

- Migración: [`2026_10_28_000038_create_incident_code_sequences_table.php`](../../../backend/database/migrations/2026_10_28_000038_create_incident_code_sequences_table.php)
- Paso de los códigos anteriores: [`2026_10_28_000039_renumber_incident_codes.php`](../../../backend/database/migrations/2026_10_28_000039_renumber_incident_codes.php)
- Acceso: [`IncidentCodes`](../../../backend/app/Modules/Incidents/Infrastructure/IncidentCodes.php) (`next`, `assignPending`)
- Formato: [`IncidentCode`](../../../backend/app/Modules/Incidents/Domain/IncidentCode.php)
- Explicación funcional: [Módulo Incidents](../../backend/incidents.md)
