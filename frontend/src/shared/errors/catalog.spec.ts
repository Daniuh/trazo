// Pruebas del catálogo: cada código se convierte en un mensaje claro, sin plantillas sin completar.
import { describe, expect, it } from 'vitest'
import { ApiError, type ApiErrorDetails } from '@/shared/http'
import { fieldMessageFor, fieldMessagesFor, messageFor, registerErrorMessages } from './catalog'
import { fieldMessages } from './fieldMessages'
import { generalMessages } from './generalMessages'

const httpError = (code: string, details: ApiErrorDetails = {}) =>
  new ApiError('http', code, { status: 400, traceId: 'T-1', ...details })

describe('catálogo de errores', () => {
  it('traduce un código general', () => {
    expect(messageFor(httpError('FORBIDDEN_ACTION'))).toBe(generalMessages.FORBIDDEN_ACTION)
  })

  it('incluye el código de soporte en un error interno', () => {
    expect(messageFor(httpError('INTERNAL_ERROR', { traceId: 'ABC123' }))).toContain('ABC123')
  })

  it('usa los segundos de espera cuando la API los informa', () => {
    const error = httpError('TOO_MANY_REQUESTS', { status: 429, retryAfter: 30 })

    expect(messageFor(error)).toContain('Espera 30 segundos')
  })

  it('no deja la plantilla vacía si no llegan los segundos', () => {
    expect(messageFor(httpError('TOO_MANY_REQUESTS'))).toContain('Espera unos segundos')
  })

  it('usa un mensaje útil, con código de soporte, ante un código desconocido', () => {
    expect(messageFor(httpError('CODIGO_NUEVO', { traceId: 'XYZ' }))).toContain('XYZ')
  })

  it('permite a cada módulo registrar sus códigos de negocio', () => {
    registerErrorMessages({ INVALID_STATUS_TRANSITION: 'No puedes pasar de {from} a {to}.' })

    expect(messageFor(httpError('INVALID_STATUS_TRANSITION', { params: { from: 'Cerrado', to: 'Abierto' } }))).toBe(
      'No puedes pasar de Cerrado a Abierto.',
    )
  })

  it('arma el mensaje de un campo con sus parámetros', () => {
    expect(fieldMessageFor({ code: 'TOO_LONG', params: { max: 150 } })).toBe('Puede tener máximo 150 caracteres.')
  })

  it('explica una fecha en el pasado sin mostrar "now"', () => {
    expect(fieldMessageFor({ code: 'DATE_TOO_EARLY', params: { date: 'now' } })).toBe(fieldMessages.DATE_IN_PAST)
  })

  it('devuelve el primer mensaje de cada campo para el formulario', () => {
    const error = httpError('VALIDATION_FAILED', {
      fieldErrors: {
        title: [{ code: 'REQUIRED' }, { code: 'TOO_LONG', params: { max: 150 } }],
        email: [{ code: 'INVALID_EMAIL' }],
      },
    })

    expect(fieldMessagesFor(error)).toEqual({ title: fieldMessages.REQUIRED, email: fieldMessages.INVALID_EMAIL })
  })

  it('ningún mensaje queda con una variable sin reemplazar', () => {
    const params = { min: 1, max: 2, size: 3, date: '2026-01-01' }
    const all = Object.keys(fieldMessages).map((code) => fieldMessageFor({ code, params }))

    all.forEach((message) => expect(message).not.toMatch(/\{\w+\}/))
  })
})

describe('parámetros numéricos', () => {
  it('se muestran con separador de miles', () => {
    registerErrorMessages({ TEST_NUMBERS: 'Son {total} de {max}.' })

    expect(messageFor(httpError('TEST_NUMBERS', { params: { total: 60000, max: 50000 } }))).toBe(
      'Son 60.000 de 50.000.',
    )
  })
})
