---
title: Rendimiento del listado y del dashboard
description: Cómo se validaron los índices del listado y del dashboard con EXPLAIN sobre 100.000 incidentes, y los resultados.
audience: tecnico
area: base-de-datos
module: incidents
order: 5
---

# Rendimiento del listado y del dashboard

El listado de incidentes es la consulta más usada y la que más crece. Sus índices se validaron con `EXPLAIN` sobre **100.000 incidentes** generados por el seeder de volumen, en la misma base MySQL 8.4 del proyecto.

## Cómo se mide

1. Cargar la base de pruebas con volumen (alrededor de un minuto, porque cada incidente trae su historial: unas 520.000 filas en `incident_activities`):
   ```bash
   docker compose -p trazo exec -e DB_DATABASE=trazo_test -e SEED_INCIDENTS=100000 app php artisan migrate:fresh --seed --force
   ```
2. Ejecutar el script de medición, que corre el listado real y pide el `EXPLAIN` del conteo y de la página:
   ```bash
   docker compose -p trazo exec -e DB_DATABASE=trazo_test app php artisan tinker --execute "require base_path('database/benchmarks/explain-incident-list.php');"
   ```
3. Para el dashboard, el script mide cada fuente de datos sin caché, para quien ve todo, un agente y un solicitante:
   ```bash
   docker compose -p trazo exec -e DB_DATABASE=trazo_test app php artisan tinker --execute "require base_path('database/benchmarks/explain-dashboard.php');"
   ```

Cada escenario se ejecuta tres veces y se reporta el mejor tiempo: en Docker sobre Windows una sola medición tiene mucho ruido (la misma consulta, con el mismo plan, puede tardar el triple de una vez a otra).

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

- **El conteo del agente recorría la tabla.** Su filtro (propios, asignados o sin asignar) abarca el 40 % de las filas. Desde los índices del dashboard (abajo), se resuelve solo con el índice.
- **El orden por prioridad u estado ordena en memoria.** Ordena por el nivel del catálogo (`priorities.level`), que vive en otra tabla. Copiar el nivel a `incidents` repetiría un dato, y la base de datos está normalizada. Con 17.000 filas filtradas, tarda 72 ms.
- **Paginación por número de página.** Pedir la página 4.000 de 5.000 es más lento que pedir la primera. Es lo que pide la prueba (`page` y `per_page`), y nadie recorre miles de páginas a mano: los filtros y la búsqueda acotan antes.

Las mejoras para un volumen mucho mayor (réplica de lectura, tabla resumen para los conteos y motor de búsqueda externo) están documentadas en [ADR-0014](../decisiones/0014-crecimiento.md).

## Dashboard (100.000 incidentes, sin caché)

En uso real, cada fuente queda en caché hasta que cambia un incidente o pasan 60 segundos (ver [Módulo Dashboard](../backend/dashboard.md)). Esta medición es el peor caso: la primera lectura después de un cambio.

| Fuente | Quien ve todo | Agente | Solicitante | Índice |
|---|---|---|---|---|
| Por estado y prioridad (total, pendientes y los dos gráficos) | 42 ms | 57 ms | 44 ms | `dashboard_counts` (solo índice) |
| Vencidos | 5 ms | 21 ms | 25 ms | `status_id, due_date, deleted_at` (solo índice) |
| Sin asignar | 3 ms | 3 ms | 4 ms | `dashboard_assignee` (solo índice) |
| Creados por hora (90 días) | 172 ms | 133 ms | 60 ms | `created_at, deleted_at` |
| Resueltos por hora (90 días) | 140 ms | 156 ms | 53 ms | `resolved_at, deleted_at` |
| Próximos a vencer (conteo y 5 filas) | 7 ms | 28 ms | 19 ms | `status_id, due_date, deleted_at` y `due_date, deleted_at` |
| Asignados a mí (conteo y 5 filas) | 17 ms | 15 ms | 3 ms | `dashboard_assignee` |
| Carga por agente | 52 ms | 62 ms | 35 ms | `dashboard_assignee` (solo índice) |

Los datos de demostración reparten los incidentes en los últimos 60 días, así que con 100.000 hay unos 1.600 por día. Es mucho más que una mesa de ayuda normal, y sirve como peor caso:

- **Todas las fuentes responden en menos de 100 ms, salvo la tendencia de 90 días** (de 130 a 170 ms). Esa ventana abarca los 100.000 incidentes, y la consulta cuenta cada uno por hora. El tiempo depende de cuántos incidentes caen en la ventana, no del tamaño de la tabla.
- La tendencia queda en caché 60 segundos (o hasta el siguiente cambio), así que solo la paga la primera lectura.
- Si el volumen diario real se acercara a esa cifra, el paso siguiente es la tabla resumen por día ya prevista en [ADR-0014](../decisiones/0014-crecimiento.md): los días pasados no cambian y se contarían una sola vez.
- Para el solicitante, la base usa `created_by` y lee sus filas: son pocas.

### Índices que salieron de la medición

La primera medición mostró tres consultas lentas:

- **La carga por agente recorría la tabla entera** (560 ms).
- **El agente recorría la tabla en el conteo por estado y prioridad** (260 ms): su visibilidad usa `created_by` y `assigned_to`, que no estaban en el índice.
- **"Asignados a mí"** (130 ms): MySQL recorría el índice de vencimientos completo, incluidos los cerrados, para no ordenar.

La migración `2026_10_23_000017` las resolvió:

| Antes | Después |
|---|---|
| `(status_id, priority_id)` | `(status_id, priority_id, deleted_at, created_by, assigned_to)` (`dashboard_counts`) |
| `(assigned_to, status_id)` | `(assigned_to, status_id, due_date, deleted_at, created_by)` (`dashboard_assignee`) |
| `(created_at)` | `(created_at, deleted_at)` |
| — | `(resolved_at, deleted_at)` |

Además, "asignados a mí" le indica a MySQL el índice `dashboard_assignee` (`useIndex`): ordenar los pendientes de una persona es mucho más barato que recorrer todos los vencimientos.

### Efecto en el listado

Los índices nuevos empiezan igual que los anteriores, así que el listado no pierde ningún uso. Para compararlo se midió el listado con los índices anteriores y con los nuevos, seguidos y en las mismas condiciones (con la distribución de datos anterior, en la que el 60 % de los incidentes seguía pendiente):

| Escenario | Índices anteriores | Índices nuevos |
|---|---|---|
| Todo, orden por creación | 22 ms | 20 ms |
| Agente | 54 ms | 30 ms |
| Solicitante | 49 ms | 35 ms |
| Estado + prioridad, orden por prioridad | 170 ms | 117 ms |
| Vencidos, orden por vencimiento | 152 ms | 156 ms |
| Asignado a una persona | 54 ms | 16 ms |
| Rango de vencimiento | 28 ms | 26 ms |
| Búsqueda de texto | 82 ms | 74 ms |

Ese día la máquina estaba más cargada que en la primera medición del listado (arriba): el escenario más simple tardó 22 ms en lugar de 12 ms, sin cambios de código. Con esa carga, dos escenarios del listado superan los 100 ms con los índices anteriores y con los nuevos:

- El orden por prioridad, porque ordena en memoria (ver arriba).
- Los vencidos ordenados por vencimiento, porque MySQL recorre el índice de vencimientos desde los más antiguos, que en su mayoría están cerrados.

Quedan anotados como puntos a vigilar.

## Revisión general (25 de octubre de 2026)

En la pasada de calidad ([ADR-0060](../decisiones/0060-pasada-de-calidad.md)) se revisó todo lo construido después de estas mediciones.

**Slow query log** (más de 1 s), después de las corridas E2E y del escaneo activo de ZAP: **ninguna consulta de la app**. Las 32 anotadas son de las pruebas: borrar y truncar las tablas de `trazo_test` al empezar cada suite, y la inserción de 100.000 incidentes para medir la exportación.

**Lista de eventos del sistema** con 50.000 eventos (mejor de 3 intentos):

| Consulta | Antes | Después |
|---|---|---|
| Pendientes (por defecto) | 97 ms | 38 ms |
| Todos | 105 ms | 34 ms |
| Críticos pendientes | 25 ms | 30 ms |
| Por tipo | 1 ms | 1 ms |

El `EXPLAIN` mostraba que MySQL empezaba por la tabla de tipos y ordenaba todo en una tabla temporal. Se cambió a `LEFT JOIN` (la clave foránea garantiza el tipo) y los filtros del tipo a una subconsulta: ahora empieza por los eventos. Sigue ordenando en memoria, pero con un volumen real mucho menor, porque los eventos se agrupan por huella y 50.000 serían 50.000 fallas distintas.

**Exportación:** 50.000 filas en 2,1 s con la memoria estable ([Incidents](../backend/incidents.md#exportación)).

**Sin N+1** en ningún listado: incidentes, auditoría, dashboard, personas, roles y eventos del sistema tienen su prueba que compara las consultas con pocos y con muchos registros.

**Front, sobre el build de producción:**

| Pantalla | Celular lento (CPU ×4, red 4G): listo / LCP | Portátil: listo / LCP | CLS |
|---|---|---|---|
| Ingreso | 1,7 s / 1,6 s | 1,1 s / 1,1 s | 0 |
| Inicio (dashboard) | 2,2 s / 2,1 s | 1,1 s / 1,1 s | 0 |
| Incidentes | 3,3 s / 3,0 s | 1,5 s / 1,2 s | 0 |

- **Carga inicial:** unos 175 kB comprimidos.
  - JavaScript principal: 50 kB. Vue y Quasar: 27 kB. Estructura de la app: 38 kB. Mensajes y cliente HTTP: 22 kB.
  - CSS: 38 kB, casi todo de Quasar.
- **Cada pantalla se descarga al entrar** (entre 5 y 25 kB); el editor del dashboard (44 kB), solo al personalizar.
- Los tiempos del listado en celular incluyen la latencia del Docker local en Windows, de alrededor de 1 s por petición.

## Dónde está en el código

- Consulta: [`EloquentIncidentList.php`](../../backend/app/Modules/Incidents/Infrastructure/EloquentIncidentList.php) y [`IncidentFilters.php`](../../backend/app/Modules/Incidents/Infrastructure/IncidentFilters.php)
- Migración: [`2026_10_22_000014_extend_incident_due_indexes_with_deleted_at.php`](../../backend/database/migrations/2026_10_22_000014_extend_incident_due_indexes_with_deleted_at.php)
- Scripts de medición: [`explain-incident-list.php`](../../backend/database/benchmarks/explain-incident-list.php) y [`explain-dashboard.php`](../../backend/database/benchmarks/explain-dashboard.php)
- Índices del dashboard: [`2026_10_23_000017_add_dashboard_indexes_to_incidents.php`](../../backend/database/migrations/2026_10_23_000017_add_dashboard_indexes_to_incidents.php) y [`EloquentDashboardQueries.php`](../../backend/app/Modules/Dashboard/Infrastructure/EloquentDashboardQueries.php)
- Seeder de volumen: [`IncidentVolumeSeeder.php`](../../backend/database/seeders/IncidentVolumeSeeder.php)
