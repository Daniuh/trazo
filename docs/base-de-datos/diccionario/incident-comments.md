---
title: incident_comments
description: Mensajes de la conversación de cada incidente.
audience: tecnico
area: base-de-datos
module: incidents
order: 23
---

# incident_comments

Un mensaje por fila de la conversación de un incidente: quién lo escribió, qué dijo y cuándo ([ADR-0076](../../decisiones/0076-conversacion-del-incidente.md)). Quien lo escribió puede borrarlo durante 5 minutos; la fila queda, el texto se vacía y la conversación muestra "Mensaje eliminado".

| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK | Clave interna; también ordena la conversación |
| `ulid` | CHAR(26) | UNIQUE, NOT NULL | Identificador público del mensaje (API) |
| `incident_id` | BIGINT UNSIGNED | FK → `incidents`, NOT NULL | Incidente al que pertenece |
| `user_id` | BIGINT UNSIGNED | FK → `users`, NOT NULL | Quién lo escribió |
| `body` | VARCHAR(2000) | NULL solo si se borró | El mensaje tal como se escribió (sin espacios en los extremos) |
| `created_at` | TIMESTAMP | NOT NULL | Cuándo se escribió (UTC) |
| `deleted_at` | TIMESTAMP | NULL | Cuándo lo borró quien lo escribió |

## Restricciones

| Restricción | Regla |
|---|---|
| `chk_incident_comments_body` | `(deleted_at IS NULL) = (body IS NOT NULL)`: un mensaje en pie tiene texto y uno borrado no lo tiene |
| FK `incident_id`, `user_id` | `RESTRICT`: ni el incidente ni la persona se borran mientras tengan mensajes; los incidentes se eliminan de forma lógica y las personas se desactivan |

## Índices

| Índice | Uso |
|---|---|
| `ulid` (único) | Encontrar un mensaje para borrarlo y pedir los anteriores a uno (`before`) |
| `(incident_id, id)` | Leer la conversación de un incidente del más nuevo al más viejo, de a 50, y contar sus mensajes |

## Sin `updated_at`

Los mensajes no se editan: lo único que cambia es el borrado, que tiene su propia columna.

## Dónde está en el código

- Migración: [`2026_11_01_000040_create_incident_comments_table.php`](../../../backend/database/migrations/2026_11_01_000040_create_incident_comments_table.php)
- Acceso: [`DatabaseIncidentComments`](../../../backend/app/Modules/Incidents/Infrastructure/DatabaseIncidentComments.php)
- Reglas: [`CommentRules`](../../../backend/app/Modules/Incidents/Domain/CommentRules.php)
- Explicación funcional: [Módulo Incidents](../../backend/incidents.md#conversación)
