// Pruebas del formato de fechas: relativas, completas y conversión del campo de fecha y hora.
import { describe, expect, it } from 'vitest'
import { formatDateTime, formatRelative, fromLocalInput, toLocalInput } from './dates'

const now = new Date('2026-10-22T15:00:00Z')

describe('formatRelative', () => {
  it.each([
    ['2026-10-22T14:59:40Z', 'hace un momento'],
    ['2026-10-22T14:40:00Z', 'hace 20 minutos'],
    ['2026-10-22T13:00:00Z', 'hace 2 horas'],
    ['2026-10-21T15:00:00Z', 'ayer'],
    ['2026-10-25T15:00:00Z', 'dentro de 3 días'],
  ])('%s se lee como "%s"', (iso, text) => {
    expect(formatRelative(iso, now)).toBe(text)
  })

  it('pasada una semana muestra la fecha completa', () => {
    expect(formatRelative('2026-10-01T15:00:00Z', now)).toBe(formatDateTime('2026-10-01T15:00:00Z'))
  })
})

describe('campo de fecha y hora', () => {
  it('ida y vuelta conserva el mismo instante', () => {
    const iso = '2026-10-22T16:10:00.000Z'

    expect(fromLocalInput(toLocalInput(iso))).toBe(iso)
  })
})
