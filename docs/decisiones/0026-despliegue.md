---
title: "ADR-0026: Despliegue en VPS con el mismo docker compose"
description: "Despliegue en VPS con el mismo docker compose"
audience: tecnico
area: decisiones
order: 36
---

# ADR-0026: Despliegue en VPS con el mismo docker compose

- **Estado:** Aceptada
- **Fecha:** 2026-10-21

## Contexto
El proyecto se presenta desplegado, y la base de datos no debe tener costo adicional.

## Alternativas evaluadas
- Plataformas administradas (Railway, Render, Fly.io).
- Un VPS que ejecuta el mismo docker compose.

## Decisión
- **Servidor:** un VPS en Contabo, con el mismo docker compose más su archivo de producción.
- **Dominio:** `daniuh.dev`, con subdominios para stage y producción.
- **HTTPS:** certificados de Let's Encrypt.
- **Base de datos:** MySQL en un contenedor, sin costo adicional.
- **Operación:** respaldo diario de la base de datos y límites de recursos por contenedor.

## Alternativas descartadas
- **Plataformas administradas:** no reutilizan el docker compose y, al sumar base de datos, Redis y workers, dejan de ser gratuitas.

## Consecuencias
- Lo que se evalúa en local es exactamente lo que corre desplegado.
