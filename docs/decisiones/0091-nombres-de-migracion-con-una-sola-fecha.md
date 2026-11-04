---
title: "ADR-0091: Nombres de migración con una sola fecha"
description: "Los archivos de migración llevan una sola fecha y su número de orden, y las bases con los nombres anteriores se ponen al día solas antes de migrar"
audience: tecnico
area: decisiones
tags: [base-de-datos]
order: 101
---

# ADR-0091: Nombres de migración con una sola fecha

- **Estado:** Aceptada.
- **Fecha:** 2026-11-01

## Contexto
Cada archivo de migración llevaba en el nombre una fecha distinta (`2026_10_21_000001_…`, `2026_10_24_000015_…`). El número de seis cifras ya era único y creciente en todo el proyecto, así que la fecha no aportaba orden y solo hacía la lista más difícil de leer. Se unificó la fecha y el orden quedó en el número.

El problema es que cada base guarda en la tabla `migrations` el nombre de cada migración que ya aplicó. Si solo se renombran los archivos, una base existente (la del sitio en línea, la de alguien que ya instaló el proyecto) cree que las 44 migraciones son nuevas e intenta crear tablas que ya existen: el arranque falla.

## Alternativas evaluadas
- Dejar los nombres como estaban.
- Renombrar los archivos y pedir a cada instalación un `UPDATE` manual de la tabla `migrations` antes de actualizar.
- Renombrar los archivos y que la aplicación ponga al día esa tabla sola, justo antes de migrar.

## Decisión
Los archivos pasan a `2026_11_01_0000NN_…`, con el mismo número y, por tanto, el mismo orden. Para Laravel ese número es la hora del día (00:00:01 a 00:00:44).

`MigrationNames` cambia el prefijo de las filas viejas (`2026_10_DD_0000NN_`) por `2026_11_01_` en la tabla de control. Se ejecuta antes de `migrate`, `migrate:status` y `migrate:rollback`, por medio del evento `CommandStarting` que Laravel emite en la consola. Solo toca filas de la tabla `migrations`, no cambia ningún dato y se puede repetir: en una base nueva o ya al día no hace nada.

## Alternativas descartadas
- **Dejarlos igual:** era lo más simple, pero no cumplía lo que se buscaba.
- **`UPDATE` manual:** depende de que cada persona lo recuerde. Si alguien actualiza sin hacerlo, el arranque se cae, y quien ya instaló el proyecto no tiene cómo saberlo.

## Consecuencias
- Una instalación nueva no nota nada. Una existente se pone al día en el primer arranque con el código nuevo, sin pasos manuales; también el sitio en línea.
- Si se escriben migraciones nuevas, siguen la numeración (`2026_11_01_000045_…` en adelante) para conservar el orden.

## Dónde está en el código
- [`MigrationNames.php`](../../backend/app/Modules/Shared/Infrastructure/Database/MigrationNames.php) y el oyente en [`AppServiceProvider.php`](../../backend/app/Providers/AppServiceProvider.php).
- [`MigrationNamesTest.php`](../../backend/tests/Feature/Database/MigrationNamesTest.php).
