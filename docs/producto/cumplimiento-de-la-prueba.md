---
title: Cumplimiento de la prueba técnica
description: Cada punto de la prueba, dónde se cumple y cómo comprobarlo.
audience: todos
area: producto
module: cumplimiento
order: 9
---

# Cumplimiento de la prueba técnica

Recorre la prueba en su mismo orden. Cada fila dice dónde se cumple y cómo verlo funcionando. Lo que se hizo de más está en [mejoras aplicadas](../mejoras/aplicadas.md) y lo que se propone a futuro, en [mejoras propuestas](../mejoras/propuestas.md).

## Tecnologías mínimas

| Pedido | Cumplimiento |
|---|---|
| PHP 8+ | PHP 8.4 ([ADR-0038](../decisiones/0038-versiones-del-stack.md)) |
| Laravel 10+ | Laravel 13 |
| MySQL o MariaDB | MySQL 8.4 LTS ([ADR-0007](../decisiones/0007-mysql.md)) |
| Vue.js 3 | Vue 3 con TypeScript ([ADR-0004](../decisiones/0004-typescript.md)) |
| Quasar o Vuetify | Quasar como plugin de Vite, confinado a la presentación ([ADR-0005](../decisiones/0005-quasar.md), [ADR-0039](../decisiones/0039-quasar-como-plugin-de-vite.md)) |
| Docker | Todo el sistema corre con `docker compose up -d` ([ADR-0025](../decisiones/0025-docker.md)) |

## Backend

### 1. Autenticación

| Pedido | Dónde | Cómo comprobarlo |
|---|---|---|
| Login | `POST /api/v1/auth/login` en [AuthController](../../backend/app/Modules/Auth/Http/AuthController.php) | Ingresar en `/ingresar` con una [credencial de prueba](../../README.md#credenciales-de-prueba) |
| Logout | `POST /api/v1/auth/logout` (revoca el token) | Círculo con las iniciales, al final del menú de abajo → Cerrar sesión |
| Protección de rutas | Middleware `auth:sanctum` en cada módulo ([rutas de Auth](../../backend/app/Modules/Auth/Http/routes.php)) y guarda del front ([authGuard](../../frontend/src/modules/auth/application/authGuard.ts)) | Abrir `/incidentes` sin sesión lleva al login; la API responde 401 `UNAUTHENTICATED` |
| Sugerencia: Sanctum | Laravel Sanctum con tokens de API ([ADR-0015](../decisiones/0015-autenticacion-sanctum.md)) | [LoginTest](../../backend/tests/Feature/Auth/LoginTest.php), [SessionTest](../../backend/tests/Feature/Auth/SessionTest.php) |

### 2. CRUD de incidentes

| Campo pedido | Columna en `incidents` ([diccionario](../base-de-datos/diccionario/incidents.md)) |
|---|---|
| id | `id` (clave interna) y `ulid` (identificador público en la API, [ADR-0010](../decisiones/0010-ulid.md)) |
| título | `title` |
| descripción | `description` |
| prioridad | `priority_id`, FK al catálogo `priorities` ([ADR-0008](../decisiones/0008-catalogos.md)) |
| estado | `status_id`, FK al catálogo `statuses`, con transiciones permitidas |
| usuario creador | `created_by`, FK obligatoria a `users` |
| usuario asignado | `assigned_to`, FK opcional a `users` |
| fecha de vencimiento | `due_date` (si no se indica, se calcula con el tiempo de la prioridad) |
| timestamps | `created_at`, `updated_at`, además de `resolved_at` y `deleted_at` |

Endpoints en [IncidentController](../../backend/app/Modules/Incidents/Http/IncidentController.php) e [IncidentWorkflowController](../../backend/app/Modules/Incidents/Http/IncidentWorkflowController.php); detalle en el [módulo Incidents](../backend/incidents.md). Pruebas: [CreateIncidentTest](../../backend/tests/Feature/Incidents/CreateIncidentTest.php) e [IncidentLifecycleTest](../../backend/tests/Feature/Incidents/IncidentLifecycleTest.php).

### 3. Listado avanzado

| Pedido | Cumplimiento |
|---|---|
| Paginación | `page` y `per_page` (máximo 100, impuesto por el back) |
| Ordenamiento | `sort` con lista blanca de campos y desempate por id |
| Búsqueda por texto | `search` con índice FULLTEXT y búsqueda por código `TZ-26-0142` ([ADR-0013](../decisiones/0013-busqueda-fulltext.md)) |
| Filtro por prioridad | `filter[priority]` |
| Filtro por estado | `filter[status]` |
| Filtro por usuario asignado | `filter[assignee]` (también "sin asignar") |
| Filtro por rango de fechas | Sobre la creación y sobre el vencimiento; además, solo vencidos |

Consulta en [EloquentIncidentList](../../backend/app/Modules/Incidents/Infrastructure/EloquentIncidentList.php) e [IncidentFilters](../../backend/app/Modules/Incidents/Infrastructure/IncidentFilters.php); decisiones en [ADR-0044](../decisiones/0044-listado-de-incidentes.md). Pruebas: [ListIncidentsTest](../../backend/tests/Feature/Incidents/ListIncidentsTest.php) y [SearchIncidentsTest](../../backend/tests/Feature/Incidents/SearchIncidentsTest.php).

### 4. Dashboard

| Pedido | Cumplimiento |
|---|---|
| Total de incidentes | "Total" en el pulso de la operación (también el widget "Total de incidentes") |
| Incidentes por estado | "Flujo de estados": cada estado con su cantidad, Cancelado como salida (también el gráfico "Por estado") |
| Incidentes vencidos | En la frase del pulso y en la columna "Vencido" del mapa de riesgo (también el widget "Vencidos") |
| Incidentes por prioridad | Columna "Total" del mapa de riesgo: todos los de cada prioridad, en cualquier estado (también el gráfico "Por prioridad", con porcentaje) |

Todo eso está en el diseño original del inicio, centrado en el riesgo ([ADR-0081](../decisiones/0081-dashboard-de-riesgo.md)). Cada cifra respeta lo que la persona puede ver y lleva al listado ya filtrado. Además: cumplimiento del plazo, mapa de riesgo por vencimiento, balance del backlog, próximos a vencer, asignados a mí y carga por agente; y en "Agregar widget", los indicadores y gráficos clásicos, sin asignar y creados contra resueltos. Consultas en [EloquentDashboardQueries](../../backend/app/Modules/Dashboard/Infrastructure/EloquentDashboardQueries.php), con caché ([ADR-0047](../decisiones/0047-metricas-del-dashboard.md)). Pruebas: [DashboardMetricsTest](../../backend/tests/Feature/Dashboard/DashboardMetricsTest.php).

### 5. Rendimiento

| Pedido | Cumplimiento |
|---|---|
| Eager loading | `with()` con columnas acotadas en el listado y el detalle de incidentes, el historial y los usuarios; los eventos del sistema y la auditoría usan JOIN directo |
| Optimización de consultas | Lecturas pesadas por query services propios ([ADR-0002](../decisiones/0002-cqrs-ligero.md)); conteos del dashboard en una sola pasada |
| Evitar N+1 | Fuera de producción, leer una relación sin `with()` lanza una excepción, así que la suite completa detecta cualquier N+1 nuevo ([carga diferida estricta](../backend/shared.md#carga-diferida-estricta)). Además, pruebas que comparan el número de consultas con 5 y con 40 registros ([ListIncidentsTest](../../backend/tests/Feature/Incidents/ListIncidentsTest.php), [ListQueriesTest](../../backend/tests/Feature/Users/ListQueriesTest.php)) |
| Índices SQL | Índices por FK, únicos, compuestos y FULLTEXT, validados con `EXPLAIN` sobre 100.000 incidentes ([rendimiento](../base-de-datos/rendimiento.md)) |
| Estructura limpia de endpoints | REST versionado en `/api/v1`, recursos en plural, errores RFC 9457 ([convenciones](../api/index.md), [OpenAPI](../api/openapi.json)) |

## Frontend

| Pedido | Dónde |
|---|---|
| Login | [LoginPage](../../frontend/src/modules/auth/presentation/LoginPage.vue) en `/ingresar` |
| Dashboard | [DashboardPage](../../frontend/src/modules/dashboard/presentation/DashboardPage.vue), página de inicio |
| Listado de incidentes | [IncidentListPage](../../frontend/src/modules/incidents/presentation/IncidentListPage.vue) en `/incidentes` |
| Crear/editar incidente | [IncidentFormPage](../../frontend/src/modules/incidents/presentation/IncidentFormPage.vue), un solo formulario para los dos casos |
| Vista detalle | [IncidentDetailPage](../../frontend/src/modules/incidents/presentation/IncidentDetailPage.vue), con historial |
| Manejo centralizado de peticiones | Un único cliente HTTP en [shared/http/client.ts](../../frontend/src/shared/http/client.ts); las vistas nunca llaman a la API directamente |
| Interceptores | Token, `X-Request-Id`, reintento de GET ante fallas de red, sesión vencida y mantenimiento ([http y errores](../frontend/http-y-errores.md)) |
| Manejo de token | [tokenStore](../../frontend/src/shared/http/tokenStore.ts); se borra solo ante `UNAUTHENTICATED` |

Pruebas de extremo a extremo de cada pantalla en las cuatro vistas: [frontend/e2e](../../frontend/e2e/incidents.spec.ts).

## Docker (obligatorio)

| Pedido | Servicio en [docker-compose.yml](../../docker-compose.yml) |
|---|---|
| `docker-compose.yml` | Base común, más `docker-compose.override.yml` para local (Mailpit) y `docker-compose.dev.yml` para desarrollar ([dos modos](../operacion/docker.md#dos-modos)) |
| Contenedor para Laravel | `app` (PHP-FPM); la misma imagen para `worker`, `scheduler` y `reverb` |
| Nginx/Apache | `gateway` (Nginx, único puerto publicado) y `frontend` (Nginx con el build de Vue) |
| MySQL/MariaDB | `mysql` 8.4, en una red interna sin salida |
| Frontend Vue | `frontend` |
| `docker compose up -d` | Un solo comando: `init` corre migraciones y seeders de forma idempotente ([Docker](../operacion/docker.md)) |

## Idealmente

| Pedido | Cumplimiento |
|---|---|
| WebSockets o tiempo real | Laravel Reverb por canales privados ([ADR-0057](../decisiones/0057-tiempo-real-en-la-practica.md)) |
| Tests con PHPUnit o Pest | Pest con MySQL real: unitarias, de API, de arquitectura y de N+1; cobertura exigida de 90 % en dominio y aplicación ([estrategia](../calidad/estrategia-de-pruebas.md)) |
| Roles y permisos | 5 roles sobre permisos granulares, con excepciones por persona ([ADR-0053](../decisiones/0053-administracion-de-usuarios.md)) |
| Exportación a Excel/CSV | CSV para Excel con los filtros del listado ([ADR-0058](../decisiones/0058-exportacion-en-la-practica.md)) |
| Logs o auditoría | Historial por incidente, auditoría global y eventos del sistema ([ADR-0056](../decisiones/0056-auditoria-global.md), [ADR-0059](../decisiones/0059-sistema-en-la-practica.md)) |

## Entregables

| Pedido | Cumplimiento |
|---|---|
| Repositorio Git | Monorepo con flujo de ramas `feature/*` → `dev` → `stage` → `main` ([ADR-0033](../decisiones/0033-flujo-git.md)) |
| README: instalación | [Instalación](../../README.md#instalación) |
| README: variables de entorno | [Variables de entorno](../../README.md#variables-de-entorno) |
| README: migraciones | [Migraciones](../../README.md#migraciones) |
| README: seeders | [Seeders](../../README.md#seeders) |
| README: credenciales de prueba | [Credenciales de prueba](../../README.md#credenciales-de-prueba) |

## Datos de prueba

| Pedido | Cumplimiento |
|---|---|
| Mínimo 500 incidentes | 600 por defecto (`SEED_INCIDENTS`), con historial completo y coherente |
| Múltiples usuarios | 9 cuentas de demostración, una o más por rol |
| Diferentes estados y prioridades | Los 6 estados y las 4 prioridades, con ~40 % de pendientes vencidos |

Lo verifica [DemoDataTest](../../backend/tests/Feature/Database/DemoDataTest.php); detalle en [seeders](../base-de-datos/seeders.md).

## Criterios de evaluación

| Criterio | Cómo se atiende |
|---|---|
| Responsive Front/Back | Cuatro vistas (celular, tablet, portátil y monitor) con breakpoints como tokens; las E2E corren en las cuatro. El back entrega lo mismo a todas y pagina con un máximo |
| Validaciones visuales Front/Back | El back valida todo y responde un código por regla y por campo; el front valida antes de enviar y muestra cada error junto a su campo |
| Manejo de errores Front/Back | RFC 9457 con código propio y `trace_id`; el front traduce cada código a un mensaje claro ([catálogo de errores](../soporte/catalogo-de-errores.md)) |
| Lo idealmente requerido en Docker | Todo lo de la sección "Idealmente" corre dentro del mismo `docker compose up -d` |
| Ejecutable | Verificado desde un clon limpio, sin pasos adicionales ([verificación](#verificación-desde-cero)) |
| Mayoría de criterios | Se cumplen todos los obligatorios y todos los ideales |

## Verificación desde cero

Se clonó el repositorio en una carpeta nueva, sin `.env` ni volúmenes previos, y se siguió el README tal cual. Las imágenes se construyeron sin caché para simular un equipo que nunca ha corrido el proyecto.

| Paso | Resultado |
|---|---|
| Construcción de imágenes (sin caché) | Unos 3 minutos |
| `docker compose up -d` | Unos 3 minutos; todos los servicios sanos e `init` terminado con código 0 |
| Datos sembrados | 600 incidentes, 9 usuarios, 6 estados, 4 prioridades y unas 3.100 entradas de historial |
| API | Login, sesión, dashboard, listado con filtros y búsqueda, catálogos y logout (después, 401) |
| Interfaz | Login, verificación en dos pasos pospuesta, dashboard, listado y formulario de creación |
| Pruebas del backend | `composer check` dentro del stack: Pint, Larastan y 350 pruebas de Pest sin fallas |

Hallazgos que quedaron corregidos en el README:

- **Rutas largas en Windows.** Clonar dentro de una carpeta muy profunda supera el límite de 260 caracteres de Windows y Git no termina el checkout.
- **Actualizar con dependencias nuevas del front.** `node_modules` vive en un volumen anónimo que Compose reutiliza al recrear el contenedor, así que una imagen reconstruida no basta.
