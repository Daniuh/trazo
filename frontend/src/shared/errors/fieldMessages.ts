// Mensajes que se muestran junto a cada campo cuando la API rechaza un dato.
// Van al lado del campo, así que no repiten su nombre. Documentado en docs/soporte/catalogo-de-errores.md
import type { MessageCatalog } from './catalog'

export const fieldMessages: MessageCatalog = {
  REQUIRED: 'Este campo es obligatorio.',
  MUST_BE_TEXT: 'Escribe un texto válido.',
  MUST_BE_INTEGER: 'Escribe un número entero.',
  MUST_BE_NUMBER: 'Escribe un número válido.',
  MUST_BE_BOOLEAN: 'Elige sí o no.',
  MUST_BE_LIST: 'Elige al menos una opción válida.',
  INVALID_EMAIL: 'Escribe un correo válido, por ejemplo nombre@empresa.com.',
  INVALID_DATE: 'Escribe una fecha válida.',
  INVALID_IDENTIFIER: 'La opción elegida no es válida. Vuelve a elegirla.',
  TOO_SHORT: 'Debe tener al menos {min} caracteres.',
  TOO_LONG: 'Puede tener máximo {max} caracteres.',
  OUT_OF_RANGE: 'Debe estar entre {min} y {max}.',
  WRONG_SIZE: 'Debe tener exactamente {size} caracteres.',
  INVALID_OPTION: 'Elige una de las opciones disponibles.',
  INVALID_REFERENCE: 'La opción elegida ya no existe. Actualiza la página y vuelve a elegir.',
  ALREADY_TAKEN: 'Este valor ya está en uso. Prueba con otro.',
  CONFIRMATION_MISMATCH: 'Los valores no coinciden. Escríbelos de nuevo.',
  DATE_TOO_EARLY: 'La fecha debe ser posterior a {date}.',
  DATE_TOO_LATE: 'La fecha debe ser anterior a {date}.',
  DATE_IN_PAST: 'La fecha no puede estar en el pasado.',
  NOT_ALLOWED: 'Este campo no se puede modificar.',
}

// Para un código de campo desconocido, mejor algo útil que nada
export const FALLBACK_FIELD_MESSAGE = 'Revisa este dato: no es válido.'
