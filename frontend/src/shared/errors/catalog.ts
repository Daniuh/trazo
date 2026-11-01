// Catálogo único de mensajes de error. El backend envía códigos y aquí se convierten en texto claro.
// Cada módulo registra sus propios códigos de negocio con registerErrorMessages.
// Documentado en docs/frontend/http-y-errores.md
import { formatNumber } from '@/shared/format/numbers'
import type { ApiError, ErrorParams, FieldError } from '@/shared/http'
import { FALLBACK_FIELD_MESSAGE, fieldMessages } from './fieldMessages'
import { formatWait } from './formatWait'
import { generalMessages } from './generalMessages'

export type MessageCatalog = Record<string, string>

const errorMessages: MessageCatalog = { ...generalMessages }

// Fechas relativas que Laravel puede enviar como parámetro
const RELATIVE_DATES = new Set(['now', 'today'])

export function registerErrorMessages(messages: MessageCatalog): void {
  Object.assign(errorMessages, messages)
}

// Reemplaza cada {nombre} de la plantilla por su valor; los números van con separador de miles (50.000)
function fill(template: string, params: ErrorParams): string {
  return template
    .replace(/\{(\w+)\}/g, (_, key: string) => {
      const value = params[key]
      return typeof value === 'number' ? formatNumber(value) : String(value ?? '')
    })
    .trim()
}

export function messageFor(error: ApiError): string {
  const template = errorMessages[error.code] ?? (errorMessages.UNKNOWN_ERROR as string)
  const params: ErrorParams = {
    ...error.params,
    traceId: error.traceId ?? 'sin código',
    wait: formatWait(error.retryAfter),
  }

  return fill(template, params)
}

export function fieldMessageFor(error: FieldError): string {
  const params = error.params ?? {}

  // "Posterior a now" no le dice nada a nadie
  if (error.code === 'DATE_TOO_EARLY' && RELATIVE_DATES.has(String(params.date))) {
    return fieldMessages.DATE_IN_PAST as string
  }

  const template = fieldMessages[error.code] ?? errorMessages[error.code] ?? FALLBACK_FIELD_MESSAGE
  return fill(template, params)
}

// Primer mensaje de cada campo, listo para mostrar en un formulario
export function fieldMessagesFor(error: ApiError): Record<string, string> {
  return Object.fromEntries(
    Object.entries(error.fieldErrors)
      .filter(([, errors]) => errors.length > 0)
      .map(([field, errors]) => [field, fieldMessageFor(errors[0] as FieldError)]),
  )
}
