// Mensajes de los errores de la administración de usuarios. Se registran solos en el catálogo al arrancar la app.
// Documentado en docs/soporte/catalogo-de-errores.md
import type { MessageCatalog } from '@/shared/errors'

const messages: MessageCatalog = {
  USER_SELF_MANAGEMENT:
    'Tu propio rol, tus permisos, tu estado y tu acceso los cambia otra persona que administre Trazo.',
  USER_OUTRANKED: 'Esta persona tiene un rol de tu mismo nivel o superior, así que no puedes cambiar su cuenta.',
  USER_ROOT_FULL_ACCESS:
    'El Super admin tiene todos los permisos por su rol, así que no se le conceden ni se le quitan permisos uno por uno.',
  USER_ALREADY_ACTIVE: 'Esta cuenta ya está activa. Recarga la página para ver su estado actual.',
  USER_ALREADY_INACTIVE: 'Esta cuenta ya está desactivada. Recarga la página para ver su estado actual.',
  USER_MFA_NOT_ENABLED:
    'Esta persona no tiene activa la verificación en dos pasos, así que no hay nada que restablecer.',
  ROLE_NOT_ASSIGNABLE: 'No puedes dar ese rol: solo puedes asignar roles por debajo del tuyo.',
  PERMISSION_NOT_GRANTABLE:
    'Solo puedes conceder permisos que tú también tienes. Revisa los permisos marcados y vuelve a intentarlo.',
  LAST_SUPER_ADMIN:
    'Trazo necesita al menos un Super admin activo. Antes de este cambio, da el rol de Super admin a otra persona.',
  REAUTHENTICATION_REQUIRED: 'Para confirmar este cambio, escribe el código de tu aplicación de autenticación.',
  REAUTHENTICATION_UNAVAILABLE:
    'Para hacer este cambio necesitas tener activa la verificación en dos pasos. Actívala desde Mi cuenta y vuelve a intentarlo.',
  REAUTHENTICATION_LOCKED: 'Escribiste varios códigos incorrectos seguidos. Espera {wait} y vuelve a intentarlo.',

  // Roles
  ROLE_OUTRANKED: 'Este rol está en tu nivel o por encima, así que no lo puedes cambiar.',
  ROLE_LOCKED: 'El rol Super admin tiene siempre todos los permisos, así que no se edita ni se elimina.',
  ROLE_SYSTEM: 'Este es uno de los roles base de Trazo, así que no se puede eliminar. Sí puedes editarlo.',
  ROLE_IN_USE: 'Hay personas con este rol. Cámbiales el rol antes de eliminarlo.',
  ROLE_LEVEL_NOT_ALLOWED: 'Elige un nivel por debajo del tuyo: como máximo {max}.',
  ROLE_CODE_FORMAT:
    'Usa solo letras minúsculas sin tildes, números y guion bajo, empezando por una letra (por ejemplo, soporte_n2).',
}

export default messages
