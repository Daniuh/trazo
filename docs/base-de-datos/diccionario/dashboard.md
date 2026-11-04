---
title: dashboard_widgets y user_dashboard_widgets
description: Catálogo de widgets del dashboard y diseño guardado de cada persona.
audience: tecnico
area: base-de-datos
module: dashboard
order: 16
---

# dashboard_widgets

Catálogo de widgets. Una fila por widget que el sistema sabe calcular.

| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | SMALLINT UNSIGNED | PK | Clave interna |
| `code` | VARCHAR(40) | UNIQUE, NOT NULL | Clave natural estable (`incidents_by_status`); la usan la API y el código |
| `name` | VARCHAR(60) | NOT NULL | Título que ve la persona |
| `description` | VARCHAR(200) | NOT NULL | Explicación del ícono de información |
| `kind` | VARCHAR(10) | NOT NULL, CHECK `kpi`, `chart` o `list` | Cómo se dibuja |
| `permission_id` | SMALLINT UNSIGNED | FK → `permissions`, RESTRICT, NULL | Permiso que exige; vacío = lo ve todo el que entra al dashboard |
| `sort_order` | SMALLINT UNSIGNED | NOT NULL | Orden en el diseño original, de diez en diez para poder intercalar |
| `min_w`, `max_w` | TINYINT UNSIGNED | NOT NULL, CHECK | Ancho mínimo y máximo en columnas (de 1 a 12) |
| `min_h`, `max_h` | TINYINT UNSIGNED | NOT NULL, CHECK | Alto mínimo y máximo en filas |
| `default_w`, `default_h` | TINYINT UNSIGNED | NOT NULL, CHECK | Tamaño en el diseño original, dentro de los topes |
| `is_active` | BOOLEAN | NOT NULL, por defecto 1 | Un widget retirado deja de ofrecerse sin borrar el diseño de nadie |
| `in_default_layout` | BOOLEAN | NOT NULL, por defecto 1 | Forma parte del diseño original. Los demás se ofrecen en "Agregar widget" y aparecen ocultos ([ADR-0081](../../decisiones/0081-dashboard-de-riesgo.md)) |
| `created_at`, `updated_at` | TIMESTAMP | | |

Las restricciones CHECK garantizan topes coherentes: `1 ≤ min_w ≤ default_w ≤ max_w ≤ 12` y `1 ≤ min_h ≤ default_h ≤ max_h`.

**Índices:** `code` (único), `permission_id` (FK) y `(is_active, sort_order)` para leer el catálogo en orden.

# user_dashboard_widgets

Diseño de cada persona. Sin filas, la persona ve el diseño original.

| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `user_id` | BIGINT UNSIGNED | PK, FK → `users`, CASCADE | El diseño es una preferencia: se va con la persona |
| `dashboard_widget_id` | SMALLINT UNSIGNED | PK, FK → `dashboard_widgets`, CASCADE | Y con el widget |
| `x` | TINYINT UNSIGNED | NOT NULL | Columna de inicio (0 a 11) |
| `y` | SMALLINT UNSIGNED | NOT NULL | Fila de inicio |
| `w`, `h` | TINYINT UNSIGNED | NOT NULL | Ancho y alto |
| `is_visible` | BOOLEAN | NOT NULL | Oculto conserva su lugar, para volver a donde estaba |
| `created_at`, `updated_at` | TIMESTAMP | | |

CHECK `w ≥ 1`, `h ≥ 1` y `x + w ≤ 12`: un widget nunca se sale de la grilla, aunque alguien escriba directo en la base. Los choques entre widgets y los topes de cada uno los valida el dominio.

**Índices:** la clave primaria `(user_id, dashboard_widget_id)` y `dashboard_widget_id` (FK).

## Dónde está en el código

- Migraciones: [`000015`](../../../backend/database/migrations/2026_11_01_000015_create_dashboard_widgets_table.php) y [`000016`](../../../backend/database/migrations/2026_11_01_000016_create_user_dashboard_widgets_table.php)
- Seeder: [`DashboardWidgetSeeder`](../../../backend/database/seeders/DashboardWidgetSeeder.php)
- Lectura y escritura: [`DatabaseWidgetCatalog`](../../../backend/app/Modules/Dashboard/Infrastructure/DatabaseWidgetCatalog.php) y [`DatabaseLayoutStore`](../../../backend/app/Modules/Dashboard/Infrastructure/DatabaseLayoutStore.php)
- Módulo: [Dashboard](../../backend/dashboard.md)
