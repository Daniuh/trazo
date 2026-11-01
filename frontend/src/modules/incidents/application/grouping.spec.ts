// Lista agrupada: secciones seguidas con su total y texto del plazo de cada incidente.
import { describe, expect, it } from 'vitest'
import { deadlineView, formatSpan } from '../domain/deadline'
import { groupSections } from '../domain/grouping'
import type { Deadline } from '../domain/incident'
import type { IncidentSummary } from '../domain/listing'

const item = (id: string, changes: Partial<IncidentSummary> = {}): IncidentSummary =>
  ({
    id,
    status: { code: 'open', name: 'Abierto', isFinal: false },
    priority: { code: 'high', name: 'Alta', level: 3 },
    assignee: null,
    deadline: { bucket: 'overdue', timeUsed: 100, resolvedOnTime: null },
    ...changes,
  }) as IncidentSummary

describe('groupSections', () => {
  it('corta la página en secciones seguidas con el total que manda la API', () => {
    const items = [
      item('1'),
      item('2'),
      item('3', { deadline: { bucket: 'today', timeUsed: 80, resolvedOnTime: null } }),
    ]

    expect(groupSections(items, 'due', { overdue: 42 })).toEqual([
      { key: 'overdue', label: 'Vencidos', total: 42, items: [items[0], items[1]] },
      { key: 'today', label: 'Vencen hoy', total: null, items: [items[2]] },
    ])
  })

  it('agrupa por estado, prioridad y asignado con sus nombres', () => {
    const pedro = item('2', { assignee: { id: 'P1', name: 'Pedro' } })

    expect(groupSections([item('1')], 'status', {})[0]).toMatchObject({ key: 'open', label: 'Abierto' })
    expect(groupSections([item('1')], 'priority', {})[0]).toMatchObject({ key: 'high', label: 'Alta' })
    expect(
      groupSections([item('1'), pedro], 'assignee', { none: 3 }).map(({ key, label, total }) => [key, label, total]),
    ).toEqual([
      ['none', 'Sin asignar', 3],
      ['P1', 'Pedro', null],
    ])
  })

  it('sin grupo es una sola lista, y nada si no hay incidentes', () => {
    expect(groupSections([item('1')], 'none', {})).toEqual([{ key: 'all', label: '', total: null, items: [item('1')] }])
    expect(groupSections([], 'none', {})).toEqual([])
  })
})

describe('deadlineView', () => {
  const now = new Date('2026-10-22T15:00:00Z')
  const pending = (bucket: Deadline['bucket'], timeUsed: number): Deadline => ({
    bucket,
    timeUsed,
    resolvedOnTime: null,
  })

  it('muestra lo que queda con su tono según el avance', () => {
    expect(deadlineView(pending('today', 40), '2026-10-22T20:00:00Z', null, now)).toEqual({
      percent: 40,
      tone: 'ok',
      text: 'Quedan 5 h',
    })
    expect(deadlineView(pending('today', 75), '2026-10-22T15:40:00Z', null, now)).toMatchObject({
      tone: 'warning',
      text: 'Quedan 40 min',
    })
  })

  it('un vencido va lleno y en rojo', () => {
    expect(deadlineView(pending('overdue', 100), '2026-10-20T15:00:00Z', null, now)).toEqual({
      percent: 100,
      tone: 'danger',
      text: 'Venció hace 2 días',
    })
  })

  it('los terminados dicen si se resolvieron a tiempo, sin barra', () => {
    const finished = (resolvedOnTime: boolean | null): Deadline => ({
      bucket: 'finished',
      timeUsed: null,
      resolvedOnTime,
    })

    expect(deadlineView(finished(true), '2026-10-20T15:00:00Z', '2026-10-20T10:00:00Z', now)).toEqual({
      percent: null,
      tone: 'ok',
      text: 'Resuelto a tiempo',
    })
    expect(deadlineView(finished(false), '2026-10-20T15:00:00Z', '2026-10-21T16:00:00Z', now).text).toBe(
      'Resuelto 1 día tarde',
    )
    expect(deadlineView(finished(null), '2026-10-20T15:00:00Z', null, now).text).toBe('')
  })

  it('elige la unidad más grande que quepa', () => {
    expect([formatSpan(30_000), formatSpan(3 * 3_600_000), formatSpan(3 * 86_400_000)]).toEqual([
      '1 min',
      '3 h',
      '3 días',
    ])
  })
})
