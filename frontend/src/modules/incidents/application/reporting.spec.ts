// Formulario que guía: plazos legibles, atajos de vencimiento y búsqueda de parecidos.
import { describe, expect, it } from 'vitest'
import { choiceFor, dueChoices, dueDateFor, formatResolution, similarQueries } from '../domain/reporting'

describe('formatResolution', () => {
  it('dice el plazo con la unidad que corresponde', () => {
    expect([30, 240, 1440, 4320, 90].map(formatResolution)).toEqual(['30 min', '4 h', '1 día', '3 días', '90 min'])
  })
})

describe('atajos de vencimiento', () => {
  const morning = new Date(2026, 9, 31, 9, 0)
  const night = new Date(2026, 9, 31, 19, 0)

  it('"Hoy" solo antes del cierre y "Sugerido" solo al crear', () => {
    expect(dueChoices(morning, true)).toEqual(['suggested', 'today', 'tomorrow', 'other'])
    expect(dueChoices(night, true)).toEqual(['suggested', 'tomorrow', 'other'])
    expect(dueChoices(morning, false)).toEqual(['today', 'tomorrow', 'other'])
  })

  it('hoy y mañana vencen a las 6:00 p. m. en la hora local; sugerido y otra fecha no ponen fecha', () => {
    expect(dueDateFor('today', morning)).toBe(new Date(2026, 9, 31, 18, 0).toISOString())
    expect(dueDateFor('tomorrow', morning)).toBe(new Date(2026, 10, 1, 18, 0).toISOString())
    expect([dueDateFor('suggested', morning), dueDateFor('other', morning)]).toEqual([null, null])
  })

  it('reconoce el atajo de un vencimiento ya puesto', () => {
    expect(choiceFor(null, morning, true)).toBe('suggested')
    expect(choiceFor(null, morning, false)).toBe('other')
    expect(choiceFor(dueDateFor('tomorrow', morning), morning, true)).toBe('tomorrow')
    expect(choiceFor('2026-12-01T15:00:00.000Z', morning, false)).toBe('other')
  })
})

describe('similarQueries', () => {
  it('primero las dos palabras más largas y luego la más larga sola, sin palabras vacías', () => {
    expect(similarQueries('La VPN no conecta desde la sede norte')).toEqual(['conecta norte', 'conecta'])
    expect(similarQueries('Impresora no imprime')).toEqual(['impresora imprime', 'impresora'])
    expect(similarQueries('Impresora')).toEqual(['impresora'])
  })

  it('no busca si no queda nada útil', () => {
    expect(similarQueries('No va')).toEqual([])
    expect(similarQueries('desde para')).toEqual([])
  })
})
