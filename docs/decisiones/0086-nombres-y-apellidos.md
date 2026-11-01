---
title: "ADR-0086: Nombres y apellidos por separado, con el nombre completo calculado por la base"
description: "La persona tiene dos campos, Nombres y Apellidos; users.name pasa a ser una columna calculada y las listas se ordenan por apellido"
audience: tecnico
area: decisiones
tags: [datos]
order: 96
---

# ADR-0086: Nombres y apellidos por separado, con el nombre completo calculado por la base

- **Estado:** Aceptada.
- **Fecha:** 2026-11-01

## Contexto
La ficha de una persona tenía un solo campo, "Nombre completo" (`users.name`). Con eso no se podía ordenar por apellido, que es como se ordena una lista de personas en una organización. Tampoco se podía entregar el nombre a otros sistemas (nómina, recursos humanos, directorio) sin adivinar dónde terminan los nombres y empiezan los apellidos. Eso va contra la regla de la base de datos de que cada registro se pueda mapear en una integración sin un proceso de corrección.

La búsqueda por "contiene" (`LIKE '%texto%'`) no usa índice con uno ni con varios campos. Con el volumen de personas de una organización responde en milisegundos, así que la indexación no era el motivo del cambio.

## Alternativas evaluadas
Para los campos:

- Uno solo, "Nombre completo" (como estaba).
- Dos: "Nombres" y "Apellidos".
- Cuatro: primer nombre, segundo nombre, primer apellido y segundo apellido.

Para el nombre completo que se muestra:

- Quitar `name` y armarlo en cada consulta.
- Guardarlo además de los dos campos.
- Una columna calculada por MySQL.

## Decisión
- **Dos campos obligatorios, `given_names` y `family_names`** (hasta 80 caracteres cada uno). Cada uno acepta una o varias palabras: "María José" y "De la Torre Gómez".
- **`name` es una columna calculada** (`TRIM(CONCAT(given_names, ' ', family_names))`, almacenada). Nadie la escribe: la API rechaza `name` (`NOT_ALLOWED`) y el modelo solo admite las dos partes. `PersonName` aplica la misma regla en el código.
- **Las listas de personas se ordenan por apellido** y luego por nombres: la administración, el selector de asignados y el catálogo de la auditoría. Hay un índice `(is_active, family_names, given_names)`.
- **La API entrega las tres:** `name` para mostrar y `given_names` y `family_names` para editar.
- **Los nombres que ya existían** se separaron una vez en la migración (`000044`). Con 2 palabras, nombre y apellido; con 3, un nombre y dos apellidos; con 4 o más, dos nombres y el resto apellidos. Solo había datos de demostración y de pruebas.

## Alternativas descartadas
- **Un solo campo:** no permite ordenar por apellido ni entregar el dato a otros sistemas sin adivinar.
- **Cuatro campos:** obligan a partir lo que no se parte bien ("De la Torre" es un solo apellido de tres palabras) y estorban a quien tiene un solo nombre o un solo apellido.
- **Quitar `name`:** unas 40 lecturas del nombre completo (correos, chat, historial, auditoría, dashboard) tendrían que armarlo, con el riesgo de que alguna lo haga distinto.
- **Guardarlo además:** sería el mismo dato dos veces, y podría quedar desalineado si alguien actualiza una parte sin la otra.

## Consecuencias
- Todo lo que lee el nombre completo sigue igual. Solo cambiaron la creación y la edición de personas.
- El historial y la auditoría conservan el nombre completo del momento, como antes.
- Si se agregan personas por otro camino (una importación, por ejemplo), hay que escribir las dos partes; `name` no se puede escribir.

## Dónde está en el código
- Migración: [`2026_11_01_000044_split_user_names.php`](../../backend/database/migrations/2026_11_01_000044_split_user_names.php).
- Regla: [`PersonName.php`](../../backend/app/Modules/Users/Domain/PersonName.php).
- Validación: [`UserRequest.php`](../../backend/app/Modules/Users/Http/Requests/UserRequest.php).
- Front: [`UserProfileFields.vue`](../../frontend/src/modules/admin/presentation/components/UserProfileFields.vue) y [`profileRules.ts`](../../frontend/src/modules/admin/application/profileRules.ts).
- Tabla: [users](../base-de-datos/diccionario/users.md).
