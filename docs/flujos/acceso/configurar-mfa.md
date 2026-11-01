---
title: Configurar la verificación en dos pasos
description: Escanear el QR, confirmar con el primer código y guardar los códigos de recuperación, ya sea durante el ingreso o desde Mi cuenta.
audience: tecnico
area: flujos
module: auth
order: 2
---

# Configurar la verificación en dos pasos

Todos los roles exigen la verificación en dos pasos (MFA), con un ingreso de gracia. Por eso se llega a esta pantalla de tres formas:

- **Sugerida:** es el primer ingreso de la persona. Ya tiene la sesión abierta y puede elegir "Configurar más tarde".
- **Obligatoria:** ya usó el ingreso de gracia. Tiene un token a medias que solo sirve para configurar el MFA; si cancela, vuelve al login.
- **Voluntaria:** desde Mi cuenta, con "Cancelar".

```flujo
carriles: [Persona, Navegador, API, Base de datos, Correo]
pasos:
  - id: llega
    carril: Navegador
    tipo: inicio
    texto: Abre /configurar-mfa
    detalle: 'El modo sale del estado de la sesión: token a medias con `mfa_setup` (obligatoria), sesión abierta con `?sugerido=1` (sugerida) o sesión abierta sin él (voluntaria).'
    va: empieza
  - id: empieza
    carril: Navegador
    texto: Envía POST /auth/mfa/setup
    va: activo
  - id: activo
    carril: API
    tipo: decision
    texto: ¿Ya tiene el MFA activo?
    si: e409
    no: secreto
  - id: e409
    carril: API
    tipo: error
    texto: Responde 409
  - id: secreto
    carril: Base de datos
    texto: Guarda un secreto nuevo, cifrado y sin confirmar
    detalle: 'El secreto se cifra con la clave de la aplicación (`user_mfa_factors.secret`, cast `encrypted`) y no vale hasta que se confirma. La API lo entrega solo en esta respuesta, junto con la dirección `otpauth://` del QR.'
    va: qr
  - id: qr
    carril: Navegador
    texto: Muestra el QR y la clave en grupos de 4
    detalle: El QR se dibuja en SVG desde la matriz de la librería `qrcode`, sin `v-html`. La clave en grupos de 4 sirve para escribirla a mano si la cámara no lee el QR.
    va: escanea
  - id: escanea
    carril: Persona
    texto: Escanea el QR y escribe el primer código
    detalle: Con cualquier aplicación de autenticación (Google Authenticator, Microsoft Authenticator, 1Password…). La cuenta aparece como "Trazo" y el correo.
    va: confirma
  - id: confirma
    carril: Navegador
    texto: Envía POST /auth/mfa/setup/confirm
    detalle: 'Límite `mfa`: 5 por minuto por persona.'
    va: valido
  - id: valido
    carril: API
    tipo: decision
    texto: ¿El código corresponde al secreto?
    si: activa
    no: e422
  - id: e422
    carril: API
    tipo: error
    texto: Responde 422
  - id: activa
    carril: Base de datos
    texto: Activa el factor y guarda 8 códigos de recuperación
    detalle: Los códigos de recuperación se guardan solo como hash SHA-256 en `user_recovery_codes`. Se anota el paso de tiempo usado para que ese mismo código no sirva para entrar.
    va: aviso
  - id: aviso
    carril: Correo
    texto: Aviso de verificación activada
    detalle: Va por la cola, cifrado mientras espera, en texto plano. Si no fue la persona, sabe que debe avisar.
    va: medias
  - id: medias
    carril: API
    tipo: decision
    texto: ¿Venía de un ingreso a medias?
    si: sesion
    no: codigos
  - id: sesion
    carril: Base de datos
    texto: Cambia el token a medias por una sesión
    va: codigos
  - id: codigos
    carril: Navegador
    texto: Muestra los 8 códigos una sola vez
    detalle: Con "Copiar todos" y "Descargar .txt". Después no se pueden volver a ver; solo se pueden generar unos nuevos.
    va: guarda
  - id: guarda
    carril: Persona
    texto: Marca "Ya guardé mis códigos"
    va: sigue
  - id: sigue
    carril: Navegador
    tipo: fin
    texto: Sigue a ?volver=, al inicio o a Mi cuenta
    detalle: Mi cuenta si llegó desde ahí; si no, la página de origen o el inicio.
```

## Qué ve la persona en cada error

| Código | HTTP | Cuándo | Mensaje en pantalla |
|---|---|---|---|
| `MFA_ALREADY_ENABLED` | 409 | La cuenta ya tiene el MFA activo (por ejemplo, otra pestaña lo terminó) | Tu cuenta ya tiene activa la verificación en dos pasos. |
| `MFA_CODE_INVALID` | 422 | El código no corresponde al secreto | El código no es correcto. Escribe el que muestra ahora tu aplicación de autenticación. |
| `MFA_SETUP_NOT_STARTED` | 409 | Se confirma sin haber pedido el secreto | La configuración no está en curso. Vuelve a empezarla desde el primer paso. |
| `MFA_CHALLENGE_EXPIRED` | 401 | En el modo obligatorio pasaron más de 5 minutos desde la contraseña | Pasó demasiado tiempo desde que escribiste tu contraseña. Vuelve a ingresar para continuar. |
| `TOO_MANY_REQUESTS` | 429 | Más de 5 confirmaciones en un minuto | Hiciste demasiados intentos seguidos. Espera {wait} y vuelve a intentarlo. |

## Dónde está en el código

- [`StartMfaSetup`](../../../backend/app/Modules/Auth/Application/Mfa/StartMfaSetup.php) y [`ConfirmMfaSetup`](../../../backend/app/Modules/Auth/Application/Mfa/ConfirmMfaSetup.php): secreto, activación, códigos y sesión.
- [`MfaGate`](../../../backend/app/Modules/Auth/Domain/Mfa/MfaGate.php): cuándo se sugiere y cuándo se exige.
- [`RecoveryCode`](../../../backend/app/Modules/Auth/Domain/Mfa/RecoveryCode.php) y [`UserMfaFactor`](../../../backend/app/Modules/Auth/Infrastructure/Models/UserMfaFactor.php): códigos con hash y secreto cifrado.
- [`MfaController`](../../../backend/app/Modules/Auth/Http/MfaController.php): rutas `setup` y `setup/confirm`.
- [`MfaSetupPage`](../../../frontend/src/modules/auth/presentation/mfa/MfaSetupPage.vue) y [`RecoveryCodesPanel`](../../../frontend/src/modules/auth/presentation/mfa/RecoveryCodesPanel.vue): los tres modos y la entrega de los códigos.
- Explicación del módulo: [Módulo Auth](../../backend/auth.md). Flujo anterior: [Ingreso con verificación en dos pasos](ingreso.md).
