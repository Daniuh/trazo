---
title: "ADR-0004: TypeScript en el frontend"
description: "TypeScript en el frontend"
audience: tecnico
area: decisiones
order: 14
---

# ADR-0004: TypeScript en el frontend

- **Estado:** Aceptada
- **Fecha:** 2026-10-21

## Contexto
La prueba pide Vue.js 3. El frontend maneja contratos con la API que conviene validar durante el desarrollo y no en producción.

## Alternativas evaluadas
- JavaScript.
- JavaScript con anotaciones JSDoc.
- TypeScript.

## Decisión
TypeScript, con `<script setup lang="ts">` en los componentes y archivos `.ts` para la lógica. Vue 3 está escrito en TypeScript y lo soporta oficialmente, así que el framework solicitado no cambia.

## Alternativas descartadas
- **JavaScript:** los errores en la forma de los datos aparecen recién al ejecutar.
- **JSDoc:** da parte del beneficio, con más ruido en el código y menos garantías.

## Consecuencias
- Los cambios en los contratos se detectan en todo el front al compilar.
- La verificación de tipos (`vue-tsc`) forma parte del CI.
