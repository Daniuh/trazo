// Calendario y "Mi día" de "Mis incidentes": grilla del mes de lunes a domingo, agenda, mes en la URL y los bloques de
// hoy sin repetir incidentes.
import { flushPromises } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import { withSetup } from '@/shared/testing/withSetup'
import { agendaDays, dayKey, monthGrid, parseMonth, shiftMonth } from '../domain/calendar'
import type { Incident } from '../domain/incident'
import { daySections } from '../domain/myDay'
import { useCalendarMonth } from './useCalendarMonth'

// Fechas locales, como las ve quien mira
const at = (year: number, month: number, day: number, hour = 10) => new Date(year, month - 1, day, hour).toISOString()

const card = (id: string, changes: Partial<Incident> = {}): Incident =>
  ({
    id,
    code: `TZ-26-000${id}`,
    title: `Incidente ${id}`,
    status: { code: 'open', name: 'Abierto', isFinal: false },
    priority: { code: 'low', name: 'Baja', level: 1 },
    dueDate: at(2026, 10, 30),
    isOverdue: false,
    deadline: { bucket: 'later', timeUsed: 10, resolvedOnTime: null },
    resolvedAt: null,
    ...changes,
  }) as Incident

describe('calendario', () => {
  it('arma semanas completas de lunes a domingo con cada incidente en su día', () => {
    const weeks = monthGrid(
      { year: 2026, month: 10 },
      [card('1'), card('2', { dueDate: at(2026, 10, 30, 8) })],
      new Date(2026, 9, 29),
    )

    // Octubre de 2026 empieza en jueves: la primera semana arranca el lunes 28 de septiembre
    expect(weeks).toHaveLength(5)
    expect(weeks[0]?.[0]?.key).toBe('2026-09-28')
    expect(weeks[0]?.[0]?.inMonth).toBe(false)
    expect(weeks[4]?.[6]?.key).toBe('2026-11-01')

    const days = weeks.flat()
    expect(days.find((day) => day.isToday)?.key).toBe('2026-10-29')
    // En el día, por hora
    expect(days.find((day) => day.key === '2026-10-30')?.items.map((item) => item.id)).toEqual(['2', '1'])
  })

  it('la agenda solo trae los días del mes con algo', () => {
    const agenda = agendaDays(
      { year: 2026, month: 10 },
      [card('1'), card('2', { dueDate: at(2026, 11, 1) })],
      new Date(2026, 9, 29),
    )

    expect(agenda.map((day) => day.key)).toEqual(['2026-10-30'])
  })

  it('lee el mes de la URL y avanza o retrocede cruzando el año', () => {
    expect(parseMonth('2026-10')).toEqual({ year: 2026, month: 10 })
    expect(parseMonth('2026-13')).toBeNull()
    expect(parseMonth(undefined)).toBeNull()
    expect(shiftMonth({ year: 2026, month: 12 }, 1)).toEqual({ year: 2027, month: 1 })
    expect(shiftMonth({ year: 2026, month: 1 }, -1)).toEqual({ year: 2025, month: 12 })
    expect(dayKey(new Date(2026, 0, 5))).toBe('2026-01-05')
  })

  it('el mes va en la URL; sin él, el actual', async () => {
    const { result: calendar, router } = await withSetup(() => useCalendarMonth(() => new Date(2026, 9, 29)))
    const mes = () => router.currentRoute.value.query.mes
    expect(calendar.month.value).toEqual({ year: 2026, month: 10 })

    calendar.next()
    await flushPromises()
    expect(mes()).toBe('2026-11')

    calendar.previous()
    await flushPromises()
    calendar.previous()
    await flushPromises()
    expect(calendar.month.value).toEqual({ year: 2026, month: 9 })

    calendar.today()
    await flushPromises()
    expect(mes()).toBe('2026-10')
  })
})

describe('Mi día', () => {
  it('pone cada incidente en un solo bloque, el primero que le toque', () => {
    const now = new Date(2026, 9, 29, 9)
    const sections = daySections(
      [
        card('1', { isOverdue: true, status: { code: 'in_progress', name: 'En progreso', isFinal: false } }),
        card('2', { dueDate: at(2026, 10, 29, 17) }),
        card('3', { status: { code: 'in_progress', name: 'En progreso', isFinal: false } }),
        card('4', { status: { code: 'on_hold', name: 'En espera', isFinal: false } }),
        card('5', { status: { code: 'resolved', name: 'Resuelto', isFinal: false }, resolvedAt: at(2026, 10, 28, 15) }),
        card('6', { status: { code: 'resolved', name: 'Resuelto', isFinal: false }, dueDate: at(2026, 10, 29, 12) }),
      ],
      now,
    )

    expect(Object.fromEntries(sections.map((section) => [section.key, section.items.map((item) => item.id)]))).toEqual({
      overdue: ['1'],
      dueToday: ['2'],
      inProgress: ['3'],
      onHold: ['4'],
      resolvedYesterday: ['5'],
    })
    expect(sections[0]?.empty).toBe('Nada vencido.')
  })
})
