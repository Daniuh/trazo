---
title: "ADR-0016: Argon2id y política de contraseñas"
description: "Argon2id y política de contraseñas"
audience: tecnico
area: decisiones
tags: [seguridad]
order: 26
---

# ADR-0016: Argon2id y política de contraseñas

- **Estado:** Aceptada
- **Fecha:** 2026-10-31

## Contexto
Las contraseñas deben protegerse con el estándar actual, y las cuentas deben resistir intentos de fuerza bruta.

## Alternativas evaluadas
- bcrypt, el valor por defecto de Laravel.
- Argon2id.

## Decisión
- **Hash:** Argon2id, el primer algoritmo que recomienda OWASP.
- **Requisitos:** mínimo 12 caracteres, rechazando contraseñas conocidas o filtradas.
- **Fuerza bruta:** límite de intentos con bloqueo progresivo.
- **Mensaje de login:** no revela si el correo existe ("El correo o la contraseña no son correctos"), para evitar la enumeración de usuarios. Es la única excepción a la regla de mensajes de error específicos.

## Alternativas descartadas
- **bcrypt:** sigue siendo seguro, pero resiste peor los ataques con hardware especializado.

## Consecuencias
- Verificar una contraseña consume algo más de CPU. A la escala del sistema, el costo es despreciable.
