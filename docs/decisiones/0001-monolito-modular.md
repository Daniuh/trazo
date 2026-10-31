---
title: "ADR-0001: Monolito modular con capas limpias en el backend"
description: "Monolito modular con capas limpias en el backend"
audience: tecnico
area: decisiones
order: 11
---

# ADR-0001: Monolito modular con capas limpias en el backend

- **Estado:** Aceptada
- **Fecha:** 2026-10-21

## Contexto
La prueba exige PHP 8+ y Laravel 10+. Además, el proyecto debe separar la lógica de negocio del framework, para poder crecer y, si algún día hace falta, cambiar de tecnología sin reescribir las reglas.

## Alternativas evaluadas
- MVC tradicional de Laravel (controlador, modelo y algún servicio).
- Arquitectura hexagonal o DDD estricta, con entidades de dominio independientes y mappers hacia Eloquent.
- Monolito modular con capas limpias pragmáticas.

## Decisión
Monolito modular. Cada módulo de dominio (`Auth`, `Incidents`, `Users`, `Dashboard`, `System`) se divide en cuatro capas:

- **Domain:** reglas puras, enums, excepciones de dominio y contratos. No depende de Laravel.
- **Application:** casos de uso y DTOs.
- **Infrastructure:** modelos Eloquent, repositorios y caché.
- **Http:** controladores delgados, FormRequests, Resources y Policies.

Lo transversal vive en `Shared`.

## Alternativas descartadas
- **MVC tradicional:** la lógica termina repartida entre controladores y modelos y acoplada a Eloquent. Es difícil de probar de forma aislada y de reutilizar.
- **Hexagonal o DDD estricto:** en un dominio con mucho CRUD obliga a duplicar modelos y escribir mappers para cada entidad. Es mucho código repetitivo para lo que aporta en este alcance.

## Consecuencias
- Las reglas de negocio se prueban sin base de datos y sin framework.
- Hay que respetar la dirección de las dependencias. Lo verifican pruebas de arquitectura automáticas.
- Un módulo nuevo se agrega sin tocar los existentes.
