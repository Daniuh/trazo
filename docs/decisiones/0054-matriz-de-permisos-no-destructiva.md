---
title: "ADR-0054: La matriz de permisos se siembra solo para roles y permisos nuevos"
description: "La matriz de permisos se siembra solo para roles y permisos nuevos"
audience: tecnico
area: decisiones
order: 64
---

# ADR-0054: La matriz de permisos se siembra solo para roles y permisos nuevos

- **Estado:** Aceptada
- **Fecha:** 2026-10-24

## Contexto
El servicio de inicialización corre `migrate --seed` en cada arranque. `AccessControlSeeder` volvía a insertar toda la matriz de rol y permiso, ignorando las parejas que ya existían. Eso no duplicaba nada, pero tenía un efecto oculto: si la administración le quitaba un permiso a un rol, el permiso volvía en el siguiente reinicio. Con la edición de roles y permisos desde la pantalla, ese comportamiento ya no se puede aceptar.

## Alternativas evaluadas
1. **Sembrar la pareja (rol, permiso) solo si el rol o el permiso se crean en esa misma ejecución.**
2. Guardar en una tabla qué parejas ya se sembraron alguna vez.
3. Sembrar la matriz solo con la base vacía.

## Decisión
La opción 1. El seeder anota qué roles y qué permisos son nuevos antes de insertarlos, y solo agrega las parejas donde alguno de los dos lo es. Todo corre en una transacción: si falla a mitad, no queda un rol nuevo sin sus permisos.

- Un permiso nuevo en una versión futura llega a los roles que lo deben tener.
- Un rol nuevo llega con su matriz completa.
- Lo que alguien ajustó a mano se respeta.

Los permisos que se reemplazan (como `users.manage`) se quitan con una migración de datos, que es la parte "contract" del patrón expand/contract.

## Alternativas descartadas
- **Tabla de parejas sembradas:** resuelve lo mismo con una tabla más que solo sirve al seeder.
- **Solo con la base vacía:** una base en uso nunca recibiría los permisos de una versión nueva.

## Consecuencias
- Una prueba quita un permiso a un rol, vuelve a sembrar y comprueba que no regresa.
- Si en una versión futura un rol existente debe ganar un permiso que ya existía, hace falta una migración de datos: el seeder no lo hará por su cuenta.
