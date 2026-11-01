---
title: "ADR-0042: Duración de la sesión y bloqueo progresivo por huella del correo"
description: "Duración de la sesión y bloqueo progresivo por huella del correo"
audience: tecnico
area: decisiones
tags: [seguridad]
order: 52
---

# ADR-0042: Duración de la sesión y bloqueo progresivo por huella del correo

- **Estado:** Aceptada
- **Fecha:** 2026-10-31

## Contexto
[ADR-0016](0016-contrasenas.md) pide un bloqueo progresivo ante intentos fallidos, y [ADR-0015](0015-autenticacion-sanctum.md) pide tokens con expiración. Faltaba definir tres cosas: los escalones del bloqueo, dónde se lleva la cuenta de fallos y cuánto dura una sesión. Además, el mensaje de login no debe revelar qué correos existen, y el bloqueo no puede convertirse en una forma de confirmarlo.

## Alternativas evaluadas

**Dónde llevar la cuenta de fallos:**
1. Columnas en `users` (`failed_attempts`, `locked_until`).
2. Redis, con la huella del correo como clave, exista o no la cuenta.

**Duración de la sesión:**
1. Sin vencimiento hasta cerrar sesión.
2. Solo un vencimiento absoluto.
3. Vencimiento absoluto de 8 horas más cierre tras 1 hora sin uso.

**Escalones:** bloqueo fijo (p. ej. 15 minutos tras 5 fallos) o progresivo.

## Decisión
- **Bloqueo progresivo:**
  - 5 fallos seguidos → 1 minuto.
  - 10 → 5 minutos.
  - 15 → 15 minutos.
  - 20 o más → 1 hora por cada fallo.
  - Los escalones son datos en `config/trazo.php`.
- **Cuenta de fallos en Redis, con la huella SHA-256 del correo como clave.** Se cuenta igual si la cuenta no existe. El contador vuelve a cero con un ingreso correcto o a las 24 horas del primer fallo.
- **Sesión:**
  - Vence a las 8 horas como máximo, una jornada laboral.
  - Se cierra tras 1 hora sin uso.
  - Deja de valer en cuanto la cuenta se desactiva.
- **Mismo tiempo de respuesta** con un correo inexistente: se verifica contra un hash de relleno de Argon2id.
- **Cada bloqueo se registra** en el log como advertencia. Los fallos aislados no.
- **Tokens:** llevan el prefijo `trazo_` y se limpian a diario cuando vencen.

## Alternativas descartadas
- **Columnas en `users`:** solo pueden bloquear cuentas que existen. La respuesta "cuenta bloqueada" le confirmaría a un atacante qué correos son reales, rompiendo la regla de no revelarlo. Además, cada intento fallido sería una escritura en la tabla más consultada.
- **Bloqueo fijo largo:** un atacante podría dejar a alguien sin acceso durante mucho tiempo con solo 5 intentos. El progresivo frena la fuerza bruta y casi no afecta a quien se equivoca un par de veces.
- **Sesión sin vencimiento:** un token robado serviría para siempre.
- **Solo vencimiento absoluto:** un equipo desatendido quedaría con la sesión abierta toda la jornada.

## Consecuencias
- Un ataque de fuerza bruta contra un correo queda limitado a muy pocos intentos por hora.
- **Si Redis se reinicia, los contadores se pierden.** Es aceptable: solo acorta una espera, y el límite de 5 intentos por minuto sigue activo.
- El desbloqueo manual por un administrador llega con el módulo de usuarios.
- Quien deja la aplicación sin usar más de una hora debe volver a ingresar.
