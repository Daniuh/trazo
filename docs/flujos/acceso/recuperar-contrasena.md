---
title: Recuperar la contraseña
description: Código de 6 dígitos al correo, pase de un solo uso, contraseña nueva con el segundo factor si la cuenta lo tiene, y cierre de todas las sesiones.
audience: tecnico
area: flujos
module: auth
order: 4
---

# Recuperar la contraseña

La recuperación tiene tres pantallas: el correo, el código que llega al correo y la contraseña nueva. En ningún paso la respuesta revela si el correo está registrado: un correo desconocido recibe la misma respuesta y, más adelante, el mismo error que un código equivocado.

```flujo
carriles: [Persona, Navegador, API, Base de datos, Correo]
pasos:
  - id: correo
    carril: Persona
    tipo: inicio
    texto: Escribe su correo en /recuperar
    va: pide
  - id: pide
    carril: Navegador
    texto: Envía POST /auth/password/forgot
    va: limite
  - id: limite
    carril: API
    tipo: decision
    texto: ¿Menos de 3 en 15 minutos?
    detalle: 'Límite `password-forgot`: 3 solicitudes cada 15 minutos por correo + IP.'
    si: cuenta
    no: e429
  - id: e429
    carril: API
    tipo: error
    texto: Responde 429
  - id: cuenta
    carril: API
    tipo: decision
    texto: ¿Es una cuenta activa?
    si: guarda
    no: responde
  - id: guarda
    carril: Base de datos
    texto: Guarda el código por 15 min y anula el anterior
    detalle: 'En `password_reset_codes` solo queda el HMAC-SHA256 del código, con la clave de la aplicación. Hay una sola solicitud vigente por persona.'
    va: envia
  - id: envia
    carril: Correo
    texto: Envía el código de 6 dígitos
    va: responde
  - id: responde
    carril: API
    texto: Responde 202 siempre
    detalle: La misma respuesta exista o no la cuenta.
    va: pantalla
  - id: pantalla
    carril: Navegador
    texto: Pide el código en /recuperar/codigo
    detalle: El correo vive solo en memoria (`useRecoveryStore`). "Reenviar código" se habilita a los 60 segundos.
    va: escribe
  - id: escribe
    carril: Persona
    texto: Escribe el código del correo
    va: verifica
  - id: verifica
    carril: Navegador
    texto: Envía POST /auth/password/verify-code
    detalle: 'Límite `password-reset`: 5 por minuto por correo + IP.'
    va: codigo
  - id: codigo
    carril: API
    tipo: decision
    texto: ¿Código correcto, vigente y con intentos?
    detalle: Un correo desconocido, un código vencido o uno equivocado dan el mismo error. Al quinto fallo la solicitud se anula y queda el evento `ResetCodeVoided`.
    si: pase
    no: e422a
  - id: e422a
    carril: API
    tipo: error
    texto: Responde 422
  - id: pase
    carril: Base de datos
    texto: Entrega un pase de un solo uso por 10 min
    detalle: El pase también se guarda como HMAC. La respuesta dice si la cuenta tiene MFA (`mfa_required`).
    va: nueva
  - id: nueva
    carril: Navegador
    texto: Pide la contraseña nueva en /recuperar/nueva
    detalle: Muestra los requisitos. Si la cuenta tiene MFA, pide además el código de la aplicación o uno de recuperación.
    va: elige
  - id: elige
    carril: Persona
    texto: Escribe la contraseña nueva y su confirmación
    va: cambia
  - id: cambia
    carril: Navegador
    texto: Envía POST /auth/password/reset
    va: politica
  - id: politica
    carril: API
    tipo: decision
    texto: ¿Cumple la política de contraseñas?
    detalle: 'De 12 a 128 caracteres, igual a la confirmación, sin patrones predecibles (repetida, en secuencia o con el correo) y que no esté en la lista local de 10.000 comunes ni en Have I Been Pwned, consultado por k-anonimato. Si Have I Been Pwned no responde en 3 s, sigue con la lista local.'
    si: vigente
    no: e422b
  - id: e422b
    carril: API
    tipo: error
    texto: Responde 422 por campo
  - id: vigente
    carril: API
    tipo: decision
    texto: ¿El pase sigue vigente?
    si: factor
    no: e422c
  - id: e422c
    carril: API
    tipo: error
    texto: Responde 422 vencido
  - id: factor
    carril: API
    tipo: decision
    texto: Si tiene MFA, ¿segundo factor correcto?
    detalle: Los fallos del segundo factor gastan los mismos 5 intentos de la solicitud. Usar un código de recuperación envía su aviso.
    si: guarda2
    no: e422d
  - id: e422d
    carril: API
    tipo: error
    texto: Responde 422 con lo que queda
  - id: guarda2
    carril: Base de datos
    texto: Guarda la contraseña y cierra todas las sesiones
    detalle: 'También levanta el bloqueo del login por intentos fallidos y avisa por el canal personal: las pantallas abiertas en otros equipos se cierran al instante.'
    va: aviso
  - id: aviso
    carril: Correo
    texto: Aviso de contraseña cambiada
    va: login
  - id: login
    carril: Navegador
    tipo: fin
    texto: Vuelve al login con el aviso
    detalle: '"Tu contraseña cambió y cerramos tus sesiones abiertas. Ingresa con la contraseña nueva."'
```

## Qué ve la persona en cada error

| Código | HTTP | Cuándo | Mensaje en pantalla |
|---|---|---|---|
| `TOO_MANY_REQUESTS` | 429 | Más de 3 solicitudes en 15 minutos, o más de 5 intentos de código en un minuto | Hiciste demasiados intentos seguidos. Espera {wait} y vuelve a intentarlo. |
| `PASSWORD_RESET_CODE_INVALID` | 422 | Código equivocado, vencido o anulado, o correo desconocido | El código no es correcto o ya venció. Revisa el último correo que te enviamos o pide un código nuevo. |
| `PASSWORD_RESET_EXPIRED` | 422 | El pase venció, ya se usó o se agotaron los intentos | Pasó demasiado tiempo o el código ya se usó. Pide un código nuevo para cambiar tu contraseña. |
| `PASSWORD_BREACHED` | 422 | La contraseña es común o aparece en filtraciones | Esta contraseña aparece en filtraciones conocidas o es de las más usadas. Elige una distinta, por ejemplo varias palabras que solo tú relaciones. |
| `PASSWORD_REPETITIVE` | 422 | Menos de 5 caracteres distintos o un bloque repetido | Tu contraseña repite demasiado los mismos caracteres. Usa una frase o mezcla palabras distintas. |
| `PASSWORD_SEQUENTIAL` | 422 | Casi toda la contraseña es una secuencia | Tu contraseña es casi toda una secuencia (como "abcd", "1234" o "qwerty"). Usa palabras que no vayan seguidas. |
| `PASSWORD_PERSONAL` | 422 | Contiene el correo o el nombre de la aplicación | Tu contraseña contiene tu nombre, tu correo o el nombre de la aplicación. Elige palabras que no se relacionen con tu cuenta. |
| `MFA_CODE_REQUIRED` | 422 | La cuenta tiene MFA y no se envió ningún código | Tu cuenta tiene la verificación en dos pasos: escribe el código de tu aplicación o uno de tus códigos de recuperación. |
| `MFA_CODE_INVALID` | 422 | El código de la aplicación no corresponde | El código no es correcto. Escribe el que muestra ahora tu aplicación de autenticación. |

## Dónde está en el código

- [`RequestPasswordReset`](../../../backend/app/Modules/Auth/Application/Password/RequestPasswordReset.php), [`VerifyResetCode`](../../../backend/app/Modules/Auth/Application/Password/VerifyResetCode.php) y [`ResetPassword`](../../../backend/app/Modules/Auth/Application/Password/ResetPassword.php): los tres pasos.
- [`DatabasePasswordResets`](../../../backend/app/Modules/Auth/Infrastructure/DatabasePasswordResets.php): código y pase con HMAC.
- [`NewPassword`](../../../backend/app/Modules/Auth/Http/Rules/NewPassword.php), [`WeakPasswordPatterns`](../../../backend/app/Modules/Auth/Domain/Password/WeakPasswordPatterns.php) y [`HibpBreachedPasswords`](../../../backend/app/Modules/Auth/Infrastructure/HibpBreachedPasswords.php): la política.
- [`AuthServiceProvider`](../../../backend/app/Modules/Auth/Infrastructure/AuthServiceProvider.php): límites `password-forgot` y `password-reset`, y los tiempos de [`config/trazo.php`](../../../backend/config/trazo.php).
- [`ForgotPasswordPage`](../../../frontend/src/modules/auth/presentation/password/ForgotPasswordPage.vue), [`ResetCodePage`](../../../frontend/src/modules/auth/presentation/password/ResetCodePage.vue), [`NewPasswordPage`](../../../frontend/src/modules/auth/presentation/password/NewPasswordPage.vue) y [`useRecoveryStore`](../../../frontend/src/modules/auth/application/useRecoveryStore.ts).
- Explicación del módulo: [Módulo Auth](../../backend/auth.md).
