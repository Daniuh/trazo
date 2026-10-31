---
title: Seeders
description: Datos iniciales, usuarios de demostración e idempotencia.
audience: tecnico
area: base-de-datos
order: 3
---

# Seeders

Los datos iniciales se cargan solos al levantar el stack: el servicio `init` ejecuta las migraciones con `--seed` en cada arranque. Por eso todos los seeders son **idempotentes y no destructivos**:

- Agregan lo que falta, identificándolo por su clave natural (`code` en catálogos, roles y permisos; `email` en usuarios).
- **Nunca pisan lo que ya existe.** Si un administrador ajusta, por ejemplo, el tiempo sugerido de una prioridad o quita un permiso a un rol, volver a sembrar no lo revierte. Los cambios intencionales a los datos base se hacen con una migración.
- **Solo insertan las filas que faltan** (`InsertsMissingRows`): primero consultan qué códigos existen y luego insertan los demás. No usan `INSERT IGNORE`, porque MySQL gasta un valor del autoincremental en cada intento aunque la fila ya exista. Los catálogos usan `TINYINT` (máximo 255), así que después de unas decenas de arranques el seeder fallaría y la API no volvería a levantar. Una prueba verifica que volver a sembrar no gasta ningún valor.
- En las pruebas, los seeders corren **una sola vez por ejecución** y cada prueba trabaja en una transacción que se revierte.

| Seeder | Qué carga | Entorno |
|---|---|---|
| `IncidentCatalogSeeder` | Prioridades, estados, transiciones y tipos de actividad | Todos |
| `AccessControlSeeder` | Roles, permisos y la matriz inicial | Todos |
| `DemoUserSeeder` | Usuarios de demostración | Todos **menos producción** |
| `IncidentVolumeSeeder` | Incidentes de demostración en volumen | Todos **menos producción** |

## Catálogo de widgets

`DashboardWidgetSeeder` carga los 10 widgets del dashboard con sus topes, su tamaño original y el permiso que exige cada uno (ver [diccionario](diccionario/dashboard.md)). Como los demás catálogos, agrega los que faltan por su `code` y nunca pisa los ajustes de los existentes. El `sort_order` va de diez en diez para poder intercalar widgets nuevos.

## Usuarios de demostración

| Correo | Rol |
|---|---|
| `superadmin@trazo.test` | Super admin |
| `supervisor@trazo.test` | Supervisor |
| `agente1@trazo.test`, `agente2@trazo.test`, `agente3@trazo.test` | Agente |
| `solicitante1@trazo.test`, `solicitante2@trazo.test`, `solicitante3@trazo.test` | Solicitante |

La contraseña de todos es `Trazo.Demo.2026` (variable `DEMO_USER_PASSWORD`), que cumple la política de 12 o más caracteres. Si un usuario ya existe, el seeder no cambia su contraseña.

## Incidentes de demostración

`IncidentVolumeSeeder` carga **600 incidentes** por defecto (la prueba pide al menos 500). El volumen se cambia con la variable `SEED_INCIDENTS`, y nunca se cargan en producción.

- **Idempotente:** completa hasta llegar al volumen pedido. Si ya hay 600 o más, no hace nada.
- **Datos coherentes y en español:**
  - **Flujo parejo, como una mesa de ayuda real:** los incidentes llegan a lo largo de los últimos 60 días y su estado depende de la edad. Lo que todavía está dentro de su tiempo de resolución sigue abierto. Lo más viejo ya se atendió (resuelto, cerrado o cancelado), salvo un 2 % que quedó atascado y vencido.
  - Por eso la tendencia del dashboard muestra creados y resueltos parecidos cada día (en 30 días, unos 280 creados y 215 resueltos), y no un pico artificial.
  - Con 600 incidentes quedan unos 50 pendientes, de los que cerca del 40 % está vencido.
  - Prioridades repartidas con pesos realistas; el vencimiento va del 80 % al 160 % del tiempo de resolución de la prioridad.
  - Lo resuelto y lo cerrado tiene fecha de resolución, nunca en el futuro.
  - Las asignaciones van solo a personas que atienden incidentes.
- **Siempre los mismos datos:** usa una semilla fija, así cada máquina obtiene el mismo resultado.
- **Historial completo y coherente** (`IncidentStory`): cada incidente cuenta cómo llegó a su estado, igual que si se hubiera trabajado desde la aplicación.
  - Primero "Creado", por quien lo creó.
  - Luego la asignación, cuando tiene responsable: la hace quien supervisa o la toma la propia persona.
  - Después los cambios de estado, siguiendo solo las transiciones permitidas. Por ejemplo, Abierto → En progreso → En espera → En progreso → Resuelto → Cerrado, o una cancelación desde Abierto, En progreso o En espera.
  - Quien atiende cambia el estado mientras trabaja; cerrar y cancelar lo hace quien supervisa.
  - Las fechas van en orden: "Resuelto" cae justo en la fecha de resolución, "Cerrado" llega hasta dos días después y nada queda en el futuro. La fecha de última modificación del incidente es la de su último paso.
  - Los cambios se guardan con la misma forma que usa la aplicación (código y nombre del estado; id público y nombre de la persona), así el detalle los muestra igual que los reales. No llevan IP ni navegador, porque no vienen de una petición.
  - Con 600 incidentes quedan unas 3.100 filas de historial: 600 creaciones, unas 580 asignaciones y unos 1.900 cambios de estado.
- **Rápido:** inserta por lotes de 1.000 sin Eloquent. Los 600 de siempre tardan menos de un segundo; 100.000 incidentes, con sus unas 520.000 filas de historial, alrededor de un minuto.
- Cada incidente recibe su código `INC-000123`.

En las pruebas automáticas el volumen es 0: cada prueba crea los incidentes que necesita. Solo las pruebas de los datos de demostración siembran 300 o 500, para revisarlos.

## Dónde está en el código

- [`backend/database/seeders/`](../../backend/database/seeders/), incluido [`Concerns/InsertsMissingRows.php`](../../backend/database/seeders/Concerns/InsertsMissingRows.php)
- [`backend/config/trazo.php`](../../backend/config/trazo.php): contraseña de demostración.
- Historial de cada incidente: [`Support/IncidentStory.php`](../../backend/database/seeders/Support/IncidentStory.php)
- Pruebas: [`SeederTest.php`](../../backend/tests/Feature/Database/SeederTest.php) y [`DemoDataTest.php`](../../backend/tests/Feature/Database/DemoDataTest.php), que verifica lo que pide la prueba técnica (500 o más incidentes, varios usuarios, todos los estados y prioridades) y la coherencia de cada historial
