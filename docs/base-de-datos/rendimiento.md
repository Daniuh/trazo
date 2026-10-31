---
title: Rendimiento del listado
description: Cómo se validaron los índices del listado de incidentes con EXPLAIN sobre 100.000 registros, y los resultados.
audience: tecnico
area: base-de-datos
module: incidents
order: 5
---

# Rendimiento del listado

El listado de incidentes es la consulta más usada y la que más crece. Sus índices se validaron con `EXPLAIN` sobre **100.000 incidentes** generados por el seeder de volumen, en la misma base MySQL 8.4 del proyecto.

## Cómo se mide

1. Cargar la base de pruebas con volumen (unos 20 segundos):
   ```bash
   docker compose -p trazo exec -e DB_DATABASE=trazo_test -e SEED_INCIDENTS=100000 app php artisan migrate:fresh --seed --force
   ```
2. Ejecutar el script de medición, que corre el listado real y pide el `EXPLAIN` del conteo y de la página:
   ```bash
   docker compose -p trazo exec -e DB_DATABASE=trazo_test app php artisan tinker --execute "require base_path('database/benchmarks/explain-incident-list.php');"
   ```

La suite de pruebas vuelve a crear esa base desde cero en su siguiente ejecución, así que no queda nada que limpiar.

## Resultados (100.000 incidentes, caché caliente)

Cada listado hace dos consultas importantes: el **conteo** (para el total y las páginas) y la **página** (20 filas). Las demás son de catálogos y relaciones, y no crecen con el volumen.

| Escenario | Resultados | Tiempo total | Conteo usa | Página usa |
|---|---|---|---|---|
| Todo, orden por creación | 100.000 | 12 ms | `due_date, deleted_at` (solo índice) | `created_at` (recorrido inverso, 20 filas) |
| Agente: propios, asignados y sin asignar | 40.377 | 29 ms | Tabla completa (el filtro abarca el 40 %) | `created_at` (20 filas) |
| Solicitante: propios | 12.461 | 18 ms | `created_by` | `created_at` (90 filas) |
| Estado + prioridad, orden por prioridad | 17.338 | 72 ms | `status_id, priority_id` | `status_id, priority_id` + ordenamiento en memoria |
| Vencidos, orden por vencimiento | 22.942 | 66 ms | `status_id, due_date, deleted_at` (solo índice) | `due_date, deleted_at` |
| Asignado a una persona | 17.023 | 61 ms | `assigned_to, status_id` | `created_at` (58 filas) |
| Rango de vencimiento | 55.010 | 19 ms | `status_id, due_date, deleted_at` (salto de índice) | `due_date, deleted_at` |
| Búsqueda de texto | 6.233 | 40 ms | FULLTEXT `title, description` | FULLTEXT + ordenamiento |

**Todos los escenarios responden en menos de 100 ms con 100.000 incidentes.**

## Ajuste que salió de la medición

Los conteos también filtran por `deleted_at` (borrado lógico), y los índices de vencimiento no incluían esa columna. Por eso MySQL tenía que leer cada fila para comprobarla. La migración `2026_10_22_000014` reemplazó:

| Antes | Después |
|---|---|
| `(status_id, due_date)` | `(status_id, due_date, deleted_at)` |
| `(due_date)` | `(due_date, deleted_at)` |

Los índices nuevos empiezan igual que los anteriores, así que los reemplazan sin perder ningún uso. Los resultados:

- El conteo de vencidos bajó de **27 ms a 3 ms**.
- El del rango de vencimiento bajó de **18 ms a 10 ms**.
- El conteo general ahora se resuelve solo con el índice.

## Lo que no se cambió, y por qué

- **El conteo del agente recorre la tabla.** Su filtro (propios, asignados o sin asignar) abarca el 40 % de las filas, y con esa proporción leer la tabla es lo más rápido. Aun así, tarda 29 ms.
- **El orden por prioridad u estado ordena en memoria.** Ordena por el nivel del catálogo (`priorities.level`), que vive en otra tabla. Copiar el nivel a `incidents` repetiría un dato, y la base de datos está normalizada. Con 17.000 filas filtradas, tarda 72 ms.
- **Paginación por número de página.** Pedir la página 4.000 de 5.000 es más lento que pedir la primera. Es lo que pide la prueba (`page` y `per_page`), y nadie recorre miles de páginas a mano: los filtros y la búsqueda acotan antes.

Las mejoras para un volumen mucho mayor (réplica de lectura, tabla resumen para los conteos y motor de búsqueda externo) están documentadas en [ADR-0014](../decisiones/0014-crecimiento.md).

## Dónde está en el código

- Consulta: [`EloquentIncidentList.php`](../../backend/app/Modules/Incidents/Infrastructure/EloquentIncidentList.php) y [`IncidentFilters.php`](../../backend/app/Modules/Incidents/Infrastructure/IncidentFilters.php)
- Migración: [`2026_10_22_000014_extend_incident_due_indexes_with_deleted_at.php`](../../backend/database/migrations/2026_10_22_000014_extend_incident_due_indexes_with_deleted_at.php)
- Script de medición: [`database/benchmarks/explain-incident-list.php`](../../backend/database/benchmarks/explain-incident-list.php)
- Seeder de volumen: [`IncidentVolumeSeeder.php`](../../backend/database/seeders/IncidentVolumeSeeder.php)
