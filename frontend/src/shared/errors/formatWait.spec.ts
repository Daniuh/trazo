// Pruebas del texto de espera que acompaña a los bloqueos y límites de intentos.
import { describe, expect, it } from 'vitest'
import { formatWait } from './formatWait'

describe('formatWait', () => {
  it.each([
    [1, '1 segundo'],
    [45, '45 segundos'],
    [60, '1 minuto'],
    [90, '2 minutos'],
    [300, '5 minutos'],
    [3600, '1 hora'],
    [7200, '2 horas'],
  ])('%i segundos se leen como "%s"', (seconds, text) => {
    expect(formatWait(seconds)).toBe(text)
  })

  it('sin dato dice "unos segundos"', () => {
    expect(formatWait(null)).toBe('unos segundos')
    expect(formatWait(0)).toBe('unos segundos')
  })
})
