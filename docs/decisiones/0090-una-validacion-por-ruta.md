---
title: "ADR-0090: Una validación por ruta"
description: "Cada endpoint con cuerpo tiene su propia clase de validación, sin reglas que cambien según la ruta, para que el contrato OpenAPI diga lo real"
audience: tecnico
area: decisiones
tags: [arquitectura]
order: 100
---

# ADR-0090: Una validación por ruta

- **Estado:** Aceptada.
- **Fecha:** 2026-11-01

## Contexto
Siete clases de validación servían a varias rutas y cambiaban sus reglas según la ruta o el método (`routeIs`, `isMethod`, el nombre de la ruta). Por ejemplo, una sola clase validaba el cambio de estado y la asignación, y otra la creación y la edición de un incidente.

Scramble arma el contrato OpenAPI evaluando esas reglas fuera de una petición real, así que siempre veía la misma rama. El contrato decía que "cambiar el estado" recibía `version` y `assignee` (los campos de la asignación), que crear un incidente pedía `version`, y que cambiar el rol de una persona no llevaba el rol. Como el contrato alimenta el escaneo activo de ZAP y la referencia de la API del portal de documentación, ese error se propagaba.

## Alternativas evaluadas
1. **Una clase por ruta**, con una base común para lo que comparten crear y editar.
2. **Anotar el contrato a mano** (atributos o PHPDoc de Scramble) y dejar las clases como estaban.
3. **Corregir el contrato después de exportarlo**, con un script.

## Decisión
Se elige la **1**. Cada endpoint con cuerpo o con parámetros propios tiene su clase (`ChangeStatusRequest`, `AssignIncidentRequest`, `CreateIncidentRequest`, `UpdateIncidentRequest`, `ReopenIncidentRequest`, `ApproveReopenRequest`, `CommentPageRequest`, `ChangeRoleRequest`, `ChangePermissionsRequest`, `CreateUserRequest`, `UpdateUserRequest`, `CreateRoleRequest`, `UpdateRoleRequest`). Lo común vive una sola vez en su base (`IncidentRequest`, `UserRequest`, `RoleRequest`, `UserAccessRequest`, `ReopenRequest`) o en un trait (`ReadsVersion`). Los campos que una ruta rechaza (`prohibited`) llevan un comentario que empieza por "No se envía:", que Scramble copia como descripción del campo.

## Alternativas descartadas
- **2:** el contrato y las reglas podrían separarse sin que nada lo avise; lo que se valida y lo que se documenta tienen que salir del mismo lugar.
- **3:** un arreglo después del hecho que se rompe con cualquier cambio de la API y esconde el problema en vez de quitarlo.

## Consecuencias
- El contrato describe el cuerpo real de cada endpoint, y con él la referencia de la API y el escaneo de ZAP.
- Cada clase es más corta y dice solo lo que su ruta acepta; ya no hay que leer ramas para saber qué valida.
- Agregar un endpoint con cuerpo es agregar su clase, no una rama más en una clase compartida.
- El comportamiento de la API no cambia: las pruebas de todos los endpoints siguen igual.
