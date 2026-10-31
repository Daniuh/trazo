// Mensajes de los errores de sesión y del MFA. Se registran solos en el catálogo al arrancar la app.
// Documentado en docs/soporte/catalogo-de-errores.md
import type { MessageCatalog } from '@/shared/errors'

// Código propio del front: el token del segundo paso venció (la API responde UNAUTHENTICATED)
export const MFA_CHALLENGE_EXPIRED = 'MFA_CHALLENGE_EXPIRED'

const messages: MessageCatalog = {
  // No dice cuál de los dos falló, para no revelar qué correos existen
  INVALID_CREDENTIALS: 'El correo o la contraseña no son correctos. Revísalos e inténtalo de nuevo.',
  ACCOUNT_LOCKED:
    'Por seguridad, pausamos el ingreso con este correo después de varios intentos fallidos. Vuelve a intentarlo en {wait}.',

  MFA_CODE_INVALID: 'El código no es correcto. Escribe el que muestra ahora tu aplicación de autenticación.',
  MFA_CODE_ALREADY_USED:
    'Ese código ya se usó. Espera a que tu aplicación muestre uno nuevo (cambia cada 30 segundos) y escríbelo.',
  MFA_RECOVERY_CODE_INVALID:
    'Ese código de recuperación no es válido o ya se usó. Revisa que lo hayas escrito completo, con sus 10 caracteres.',
  MFA_TOO_MANY_ATTEMPTS:
    'Por seguridad cerramos este ingreso después de varios códigos incorrectos. Vuelve a ingresar con tu correo y contraseña.',
  MFA_ALREADY_ENABLED: 'Tu cuenta ya tiene activa la verificación en dos pasos.',
  MFA_NOT_ENABLED:
    'Tu cuenta no tiene activa la verificación en dos pasos. Recarga la página para ver su estado actual.',
  MFA_SETUP_NOT_STARTED: 'La configuración no está en curso. Vuelve a empezarla desde el primer paso.',
  MFA_REQUIRED_BY_POLICY: 'Tu rol exige la verificación en dos pasos, así que no se puede desactivar.',
  MFA_NOT_PENDING: 'Ya ingresaste: no hay ningún código pendiente por verificar.',
  [MFA_CHALLENGE_EXPIRED]:
    'Pasó demasiado tiempo desde que escribiste tu contraseña. Vuelve a ingresar para continuar.',
}

// Complemento de "código incorrecto" durante el ingreso: cuántos intentos quedan antes de cerrarlo
export function attemptsLeftMessage(left: number): string {
  return left === 1 ? 'Te queda 1 intento.' : `Te quedan ${left} intentos.`
}

export default messages
