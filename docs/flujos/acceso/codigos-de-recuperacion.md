---
title: Códigos de recuperación
description: Para qué sirven los 8 códigos de un solo uso, cómo se usan al ingresar y cómo se generan unos nuevos desde Mi cuenta.
audience: tecnico
area: flujos
module: auth
order: 3
---

# Códigos de recuperación

Al activar la verificación en dos pasos, la persona recibe 8 códigos de recuperación de un solo uso. Sirven cuando no tiene el teléfono a mano: al [ingresar](ingreso.md) y al [recuperar la contraseña](recuperar-contrasena.md) puede escribir uno en lugar del código de la aplicación. Cada uso le envía un aviso por correo.

Trazo solo guarda el hash SHA-256 de cada código. Por eso no puede mostrarlos de nuevo: si la persona los pierde o se le acaban, genera unos nuevos desde Mi cuenta. Este diagrama muestra ese camino. Cuando quedan 2 o menos, Mi cuenta muestra un aviso para generarlos.

```flujo
carriles: [Persona, Navegador, API, Base de datos, Correo]
pasos:
  - id: pulsa
    carril: Persona
    tipo: inicio
    texto: Pulsa "Generar códigos nuevos" en Mi cuenta
    detalle: Mi cuenta dice cuántos códigos quedan y avisa cuando son 2 o menos.
    va: pide
  - id: pide
    carril: Navegador
    texto: Pide el código actual de la aplicación
    detalle: Diálogo `AppCodeDialog`, que avisa que los códigos anteriores dejarán de servir.
    va: escribe
  - id: escribe
    carril: Persona
    texto: Escribe el código de la aplicación
    va: envia
  - id: envia
    carril: Navegador
    texto: Envía POST /auth/mfa/recovery-codes
    detalle: 'Límite `mfa`: 5 por minuto por persona.'
    va: activo
  - id: activo
    carril: API
    tipo: decision
    texto: ¿Tiene el MFA activo?
    si: valido
    no: e409
  - id: e409
    carril: API
    tipo: error
    texto: Responde 409
  - id: valido
    carril: API
    tipo: decision
    texto: ¿Código válido y sin usar?
    detalle: 'El mismo control del ingreso: ventana de ±30 s y un código ya usado no vuelve a servir.'
    si: reemplaza
    no: e422
  - id: e422
    carril: API
    tipo: error
    texto: Responde 422
  - id: reemplaza
    carril: Base de datos
    texto: Borra los anteriores y guarda 8 nuevos con hash
    va: aviso
  - id: aviso
    carril: Correo
    texto: Aviso de códigos regenerados
    va: muestra
  - id: muestra
    carril: Navegador
    tipo: fin
    texto: Muestra los 8 códigos una sola vez
    detalle: Con "Copiar todos" y "Descargar .txt".
```

## Qué ve la persona en cada error

| Código | HTTP | Cuándo | Mensaje en pantalla |
|---|---|---|---|
| `MFA_NOT_ENABLED` | 409 | La cuenta ya no tiene el MFA (por ejemplo, un Super admin lo restableció) | Tu cuenta no tiene activa la verificación en dos pasos. Recarga la página para ver su estado actual. |
| `MFA_CODE_INVALID` | 422 | El código no corresponde | El código no es correcto. Escribe el que muestra ahora tu aplicación de autenticación. |
| `MFA_CODE_ALREADY_USED` | 422 | El código ya se usó en esa ventana de 30 s | Ese código ya se usó. Espera a que tu aplicación muestre uno nuevo (cambia cada 30 segundos) y escríbelo. |
| `TOO_MANY_REQUESTS` | 429 | Más de 5 intentos en un minuto | Hiciste demasiados intentos seguidos. Espera {wait} y vuelve a intentarlo. |

## Dónde está en el código

- [`RegenerateRecoveryCodes`](../../../backend/app/Modules/Auth/Application/Mfa/RegenerateRecoveryCodes.php): reemplazo de los códigos y aviso.
- [`RecoveryCode`](../../../backend/app/Modules/Auth/Domain/Mfa/RecoveryCode.php): generación, normalización y hash.
- [`VerifyMfa`](../../../backend/app/Modules/Auth/Application/Mfa/VerifyMfa.php): uso de un código al ingresar.
- [`AccountPage`](../../../frontend/src/modules/auth/presentation/account/AccountPage.vue) y [`RecoveryCodesPanel`](../../../frontend/src/modules/auth/presentation/mfa/RecoveryCodesPanel.vue): conteo, aviso y entrega.
- Explicación del módulo: [Módulo Auth](../../backend/auth.md).
