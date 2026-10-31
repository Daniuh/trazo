---
title: "ADR-0017: MFA con TOTP para todos los usuarios"
description: "MFA con TOTP para todos los usuarios"
audience: tecnico
area: decisiones
order: 27
---

# ADR-0017: MFA con TOTP para todos los usuarios

- **Estado:** Aceptada
- **Fecha:** 2026-10-21

## Contexto
Las cuentas con acceso a información operativa y a acciones de sistema necesitan un segundo factor. A la vez, el acceso con credenciales de prueba debe seguir siendo simple.

## Alternativas evaluadas
- Sin segundo factor.
- Códigos por correo o SMS.
- TOTP con una aplicación de autenticación.
- Notificación push o passkeys.

## Decisión
TOTP, compatible con cualquier aplicación de autenticación:
- **Login en dos pasos:** el primer token solo sirve para verificar el código.
- **Códigos de recuperación:** ocho, guardados con hash.
- **Protección del secreto:** se cifra a nivel de aplicación, un mismo código no puede usarse dos veces y hay límite de intentos.
- **Política por rol, guardada como dato:** define si el MFA es obligatorio y el periodo de gracia. Por defecto permite posponerlo en el primer login y lo exige desde el segundo.
- **Desactivación:** en local se puede desactivar con un comando documentado; en producción no.
- **Tablas propias:** `user_mfa_factors` y `user_recovery_codes`, para poder sumar otros métodos sin tocar `users`.

## Alternativas descartadas
- **Correo o SMS:** son factores más débiles. El SMS además tiene costo y es vulnerable a la clonación de SIM.
- **Push:** exige una aplicación móvil propia. Queda propuesta como evolución.
- **Passkeys:** amplían mucho el alcance. Quedan propuestas como evolución.

## Consecuencias
- La seguridad de las cuentas sube de forma significativa, con un flujo que cualquier usuario ya conoce.
