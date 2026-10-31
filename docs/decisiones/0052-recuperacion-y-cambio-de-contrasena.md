---
title: "ADR-0052: Recuperación y cambio de contraseña: pase de un solo uso, HMAC, Have I Been Pwned y segundo factor"
description: "Recuperación y cambio de contraseña: pase de un solo uso, HMAC, Have I Been Pwned y segundo factor"
audience: tecnico
area: decisiones
order: 62
---

# ADR-0052: Recuperación y cambio de contraseña: pase de un solo uso, HMAC, Have I Been Pwned y segundo factor

- **Estado:** Aceptada
- **Fecha:** 2026-10-24

## Contexto
[ADR-0018](0018-recuperacion-contrasena.md) definió la recuperación con un código de 6 dígitos por correo, más el TOTP si la cuenta tiene MFA. [ADR-0016](0016-contrasenas.md) definió la política: mínimo 12 caracteres y rechazar las contraseñas filtradas. Al construirlas había que decidir:

- Cómo guardar un código tan corto sin que una copia de la base lo entregue.
- En cuántos pasos se recupera la contraseña y cuándo se pide el segundo factor sin revelar qué correos existen.
- Cómo saber si una contraseña está filtrada.
- Qué pasa si alguien perdió el teléfono y además olvidó la contraseña.
- Qué sesiones se cierran al cambiar la contraseña desde Mi cuenta.

## Alternativas evaluadas
Guardar el código:
1. SHA-256 del código.
2. **HMAC-SHA256 con la clave de la aplicación.**
3. Argon2id, como las contraseñas.

Pasos de la recuperación:
1. Un solo formulario con el código, la contraseña nueva y el TOTP.
2. **Tres pasos: correo, código (que se cambia por un pase de un solo uso de 10 minutos) y contraseña nueva, con el segundo factor solo si la cuenta lo tiene.**

Contraseñas filtradas:
1. **Have I Been Pwned con su consulta anónima (k-anonimato), más una lista local de las 10.000 más comunes que siempre se revisa.**
2. Solo una lista local.

Teléfono perdido y contraseña olvidada:
1. Solo el código de la aplicación.
2. **El código de la aplicación o uno de los códigos de recuperación del MFA.**

Cambio desde Mi cuenta:
1. Pedir la contraseña actual y el código del MFA.
2. **Pedir la contraseña actual; cerrar las demás sesiones y dejar abierta la actual.**

## Decisión
- **HMAC con la clave de la aplicación** para el código y para el pase. Con solo un millón de combinaciones, un SHA-256 simple se descifra en segundos con una copia de la base; sin la clave, el HMAC no.
- **Tres pasos con un pase.** El primer paso responde siempre lo mismo (`202`). Un código mal escrito, vencido, con los intentos agotados o de un correo que no existe da el mismo error, sin contador visible. Cada código nuevo anula los anteriores. Tras 5 fallos (del código o del segundo factor) se anula la solicitud. El pase dice si la cuenta pide el segundo factor: saberlo no revela nada, porque ya se demostró que el correo es de la persona.
- **Have I Been Pwned más la lista local.** Solo salen los primeros 5 caracteres del SHA-1 de la contraseña, con relleno en la respuesta. Si el servicio no responde en 3 segundos, queda la lista local (SecLists, licencia MIT) y se registra en el log; no se bloquea a nadie por una caída externa. El largo máximo es 128, para que una contraseña enorme no haga trabajar de más a Argon2id.
- **Código de recuperación del MFA como alternativa al de la aplicación**, para quien perdió el teléfono y olvidó la contraseña a la vez. Sigue siendo un segundo factor: es algo que solo la persona guardó.
- **Desde Mi cuenta basta la contraseña actual**, porque la sesión ya pasó por el MFA. Se cierran las demás sesiones y la actual sigue abierta.
- **Al terminar una recuperación** se cierran todas las sesiones y se levanta el bloqueo del login para ese correo. En los dos casos llega el aviso "Tu contraseña cambió".
- **Límites:** 3 solicitudes de código cada 15 minutos y 5 intentos por minuto, por correo e IP; 5 cambios por minuto por persona desde Mi cuenta.

## Alternativas descartadas
- **SHA-256 del código:** se descifra por fuerza bruta en segundos.
- **Argon2id para el código:** protegería igual, pero el código ya vence en 15 minutos y se anula a los 5 intentos; el HMAC alcanza sin el costo de un hash lento.
- **Un solo formulario:** obligaría a mostrar siempre el campo del TOTP, aunque la cuenta no lo tenga, y un código mal escrito haría perder todo lo demás.
- **Solo una lista local:** detecta muchas menos contraseñas. Las de 12 o más caracteres casi nunca están entre las más comunes, pero sí en las filtraciones.
- **Pedir también el MFA al cambiar desde Mi cuenta:** la sesión ya lo verificó; pedirlo otra vez no suma seguridad y sí fricción.

## Consecuencias
- Las contraseñas nuevas dependen de un servicio externo para la revisión completa; si no responde, la revisión es más débil y queda registrado.
- La arquitectura prohíbe `sha1` en todo el código; `HibpBreachedPasswords` es la única excepción declarada, porque el protocolo de Have I Been Pwned lo exige y no protege ni guarda nada con él.
- Las pruebas E2E leen el código de Mailpit y devuelven la cuenta de prueba a su contraseña de demostración al terminar.
