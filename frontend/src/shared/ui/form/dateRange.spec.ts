// Pruebas del rango de fechas: días completos y texto corto.
import { describe, expect, it } from 'vitest'
import { dayBounds, rangeLabel, toDay } from './dateRange'

describe('dateRange', () => {
  it('toma el día completo en la hora local', () => {
    const { start, end } = dayBounds('2026/10/01')

    expect(toDay(start)).toBe('2026/10/01')
    expect(toDay(end)).toBe('2026/10/01')
    expect(new Date(end).getTime() - new Date(start).getTime()).toBe(86_400_000 - 1)
  })

  it('describe el rango en pocas palabras', () => {
    const october1 = dayBounds('2026/10/01').start
    const october22 = dayBounds('2026/10/22').end

    expect(rangeLabel(october1, october22)).toBe('1 de oct – 22 de oct')
    expect(rangeLabel(october1, dayBounds('2026/10/01').end)).toBe('1 de oct')
    expect(rangeLabel(october1, null)).toBe('Desde el 1 de oct')
    expect(rangeLabel(null, null)).toBe('')
  })
})
