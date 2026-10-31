---
title: "ADR-0018: Recuperación de contraseña por correo y TOTP"
description: "Recuperación de contraseña por correo y TOTP"
audience: tecnico
area: decisiones
order: 28
---

# ADR-0018: Recuperación de contraseña por correo y TOTP

- **Estado:** Aceptada
- **Fecha:** 2026-10-21

## Contexto
Los usuarios necesitan recuperar el acceso por sí mismos, sin abrir una puerta a la toma de cuentas.

## Alternativas evaluadas
- Enlace por correo.
- Solo el código del autenticador.
- Código por correo, más el del autenticador si está configurado.

## Decisión
- **Código por correo:** 6 dígitos, válido por 15 minutos y de un solo uso.
- **Segundo factor:** si el usuario tiene MFA, también se exige el código TOTP.
- **Sin enumeración:** la respuesta a la solicitud no revela si el correo existe.
- **Al completar el cambio:** se cierran todas las sesiones y se envía un aviso por correo.
- **Almacenamiento:** tabla propia `password_reset_codes`, con FK a `users`.
- **Envío de correos:** Mailpit en local y una cuenta de Gmail exclusiva del proyecto en el despliegue.

## Alternativas descartadas
- **Solo el autenticador:** bastaría con un celular robado y desbloqueado para cambiar la contraseña.
- **Tabla estándar de Laravel:** se identifica por correo y no tiene clave foránea.

## Consecuencias
- Recuperar el acceso exige dos factores distintos cuando el usuario tiene MFA.
