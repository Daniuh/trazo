// Pruebas de la lógica del selector de fecha y hora.
import { describe, expect, it } from 'vitest'
import { halfHourSlots, isPast, isPastDay, nextSlot, roundUp, slotsWith, toIso, toParts } from './dateTimeParts'

const now = new Date(2026, 9, 22, 10, 12)

describe('dateTimeParts', () => {
  it('ofrece 48 horarios, cada 30 minutos', () => {
    const slots = halfHourSlots()

    expect(slots).toHaveLength(48)
    expect(slots.slice(0, 3)).toEqual(['00:00', '00:30', '01:00'])
    expect(slots.at(-1)).toBe('23:30')
  })

  it('convierte ida y vuelta sin perder el instante', () => {
    const iso = new Date(2026, 9, 25, 10, 30).toISOString()

    expect(toParts(iso)).toEqual({ date: '2026/10/25', time: '10:30' })
    expect(toIso(toParts(iso))).toBe(iso)
  })

  it('reconoce días y horarios que ya pasaron', () => {
    expect(isPastDay('2026/10/21', now)).toBe(true)
    expect(isPastDay('2026/10/22', now)).toBe(false)
    expect(isPast({ date: '2026/10/22', time: '10:00' }, now)).toBe(true)
    expect(isPast({ date: '2026/10/22', time: '10:30' }, now)).toBe(false)
  })

  it('propone el siguiente horario libre', () => {
    expect(nextSlot(now)).toEqual({ date: '2026/10/22', time: '10:30' })
    expect(nextSlot(new Date(2026, 9, 22, 23, 45))).toEqual({ date: '2026/10/23', time: '00:00' })
  })

  it('redondea hacia arriba a la media hora, sin mover lo que ya cae justo', () => {
    expect(roundUp(new Date(2026, 9, 25, 10, 55, 30))).toEqual({ date: '2026/10/25', time: '11:00' })
    expect(roundUp(new Date(2026, 9, 25, 11, 0))).toEqual({ date: '2026/10/25', time: '11:00' })
  })

  it('conserva una hora guardada que no cae en media hora', () => {
    const slots = slotsWith('10:23')

    expect(slots).toContain('10:23')
    expect(slots.indexOf('10:23')).toBe(slots.indexOf('10:00') + 1)
  })
})
