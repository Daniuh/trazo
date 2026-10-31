---
title: "ADR-0015: Autenticación con Laravel Sanctum y token Bearer"
description: "Autenticación con Laravel Sanctum y token Bearer"
audience: tecnico
area: decisiones
order: 25
---

# ADR-0015: Autenticación con Laravel Sanctum y token Bearer

- **Estado:** Aceptada
- **Fecha:** 2026-10-21

## Contexto
Se requieren login, logout y protección de rutas. En el frontend se exige manejo de token e interceptores.

## Alternativas evaluadas
- Sanctum en modo SPA, con cookie httpOnly.
- Sanctum con token Bearer.
- JWT.

## Decisión
Sanctum con token Bearer:
- Los tokens tienen expiración y se guardan con hash.
- Se revocan al cerrar sesión.
- Mientras el MFA está pendiente, el token tiene habilidades restringidas.

En el front, el token vive en memoria y en `sessionStorage`, nunca en `localStorage`, y lo protege una CSP estricta.

## Alternativas descartadas
- **Cookie httpOnly:** resiste mejor el robo por XSS, pero oculta justamente el manejo de token que se evalúa. Queda documentada como mejora.
- **JWT:** su revocación es compleja, porque exige listas negras o tokens de muy corta duración con refresco.

## Consecuencias
- Se cumple al pie de la letra lo pedido, con mitigaciones explícitas frente al riesgo de XSS.
