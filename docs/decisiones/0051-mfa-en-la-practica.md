---
title: "ADR-0051: MFA en la práctica: token de un ingreso a medias, sin atajo local y avisos por correo"
description: "MFA en la práctica: token de un ingreso a medias, sin atajo local y avisos por correo"
audience: tecnico
area: decisiones
order: 61
---

# ADR-0051: MFA en la práctica: token de un ingreso a medias, sin atajo local y avisos por correo

- **Estado:** Aceptada
- **Fecha:** 2026-10-31

## Contexto
[ADR-0017](0017-mfa-totp.md) definió el MFA con TOTP. Al construirlo había que decidir:

- Cómo restringir el token mientras falta el segundo paso, sin tocar las rutas de cada módulo.
- Si se escribe el TOTP a mano o se usa una librería.
- Cómo se dibuja el QR.
- Cómo se guardan los códigos de recuperación.
- Si existe un atajo para apagar el MFA en local, como preveía [ADR-0017](0017-mfa-totp.md).
- Cómo prueban las E2E un flujo que depende de un teléfono.

## Alternativas evaluadas
Restringir el token del ingreso a medias:
1. Un middleware en cada grupo de rutas que exija la habilidad de sesión.
2. **Revisarlo en el autenticador de Sanctum (`authenticateAccessTokensUsing`), que ya revisa la inactividad: un token con la habilidad `mfa` solo vale en las rutas del segundo paso.**
3. Un token firmado aparte, fuera de Sanctum.

TOTP:
1. Escribir RFC 6238 a mano.
2. **`pragmarx/google2fa` (MIT, muy usada), envuelta en el puerto `OneTimePasswords`.**

QR:
1. Una imagen generada en el servidor.
2. El SVG que entrega la librería `qrcode`, insertado con `v-html`.
3. **La matriz que calcula `qrcode`, dibujada como SVG por el componente, con los colores de los tokens.**

Códigos de recuperación:
1. Hash Argon2id, como las contraseñas.
2. **SHA-256: son aleatorios, de unos 50 bits, y uno de los 8 se compara en una consulta.**

Atajo local:
1. **Ninguno: el MFA funciona igual en local que en producción.**
2. Un comando que apaga la política en local.

Pruebas E2E:
1. Apagar el MFA para las pruebas.
2. **Que las pruebas hagan de teléfono: guardan la clave de cada cuenta de prueba (fuera del repositorio) y calculan sus códigos con `otpauth`.**

## Decisión
- **El autenticador de Sanctum decide dónde vale cada token.** El de sesión completa tiene la habilidad `session`; el de un ingreso a medias, `mfa`, vence en 5 minutos y solo sirve en `auth.mfa.challenge.*` y en cerrar sesión. Cualquier módulo nuevo queda protegido sin hacer nada.
- **`pragmarx/google2fa`** detrás de un puerto, con ventana de ±1 paso y sin repetición: el último paso usado se guarda y se compara en un `UPDATE` condicional.
- **El QR se dibuja en el componente** (`AppQrCode`) a partir de la matriz: sin `v-html`, sin colores en JavaScript y sin pedir nada más al servidor.
- **SHA-256 para los códigos de recuperación**, normalizados para aceptar minúsculas, espacios y el guion en cualquier lugar.
- **Sin atajo local.** Quien evalúa la aplicación la usa como en producción: su primer ingreso puede posponer el MFA y desde el segundo lo configura con su teléfono. Esto reemplaza el comando de desactivación local que preveía [ADR-0017](0017-mfa-totp.md).
- **Avisos por correo**, en texto plano y por la cola, cuando se activa o desactiva el MFA, cuando se usa un código de recuperación y cuando se generan códigos nuevos.
- **Las E2E hacen de teléfono** con `otpauth`: guardan la clave en `frontend/e2e/.auth/` (ignorada por Git) y, si un código ya se usó, esperan el siguiente, como haría una persona.

## Alternativas descartadas
- **Middleware por grupo de rutas:** basta con olvidarlo en un módulo nuevo para que un token a medias abra datos.
- **Token aparte de Sanctum:** duplica la emisión, el vencimiento y la revocación que Sanctum ya resuelve.
- **TOTP a mano:** es criptografía; una implementación probada y mantenida es más segura que una propia.
- **Imagen del servidor:** otro endpoint, y la clave viajaría de nuevo solo para dibujarla.
- **SVG con `v-html`:** está prohibido por seguridad en todo el front.
- **Argon2id para los códigos:** cada ingreso con código de recuperación costaría hasta 8 verificaciones lentas, sin ganancia: un código aleatorio de 50 bits no se adivina por fuerza bruta.
- **Comando para apagarlo en local:** es un atajo que puede terminar usándose donde no debe, y quien evalúa no vería el MFA tal como funciona.
- **Apagar el MFA en las pruebas:** las E2E dejarían de probar el ingreso real.

## Consecuencias
- Quien prueba la aplicación necesita una aplicación de autenticación en su teléfono desde su segundo ingreso con cada cuenta. El README lo explica.
- En local, si se borra `frontend/e2e/.auth/`, las cuentas de prueba que ya tienen MFA no se pueden usar en las E2E hasta recargar los datos de demostración (`migrate:fresh --seed`). En el CI la base es nueva en cada ejecución, así que no pasa.
- El aviso al cambiar la contraseña llegará con la recuperación de contraseña (fase 3.2), con el mismo mecanismo de correo.
