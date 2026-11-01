// Frases del historial: cada tipo de cambio se lee claro para cualquier persona.
import { describe, expect, it } from 'vitest'
import type { Activity } from '../domain/incident'
import { describeActivity } from './historyText'

const laura = { id: 'L1', name: 'Laura Gómez' }
const activity = (type: string, changes: Activity['changes'] = null): Activity => ({
  type,
  user: laura,
  changes,
  note: null,
  createdAt: '2026-10-22T15:00:00Z',
})

describe('describeActivity', () => {
  it('cambio de estado con origen y destino', () => {
    const result = describeActivity(
      activity('status_changed', { status: { from: { name: 'Abierto' }, to: { name: 'En progreso' } } }),
    )

    expect(result).toEqual({ text: 'Laura Gómez cambió el estado', detail: 'Abierto → En progreso' })
  })

  it('distingue tomar el incidente de asignarlo a otra persona', () => {
    expect(describeActivity(activity('assigned', { assignee: { from: null, to: laura } })).text).toBe(
      'Laura Gómez tomó el incidente',
    )
    expect(
      describeActivity(activity('assigned', { assignee: { from: null, to: { id: 'A2', name: 'Andrés Castro' } } }))
        .text,
    ).toBe('Laura Gómez asignó el incidente a Andrés Castro')
  })

  it('enumera los campos editados en lenguaje natural', () => {
    const result = describeActivity(
      activity('updated', {
        title: { from: 'a', to: 'b' },
        priority: { from: { name: 'Media' }, to: { name: 'Alta' } },
      }),
    )

    expect(result).toEqual({ text: 'Laura Gómez editó el título y la prioridad', detail: 'Media → Alta' })
  })

  it('menciona la asignación inicial al crear', () => {
    expect(
      describeActivity(activity('created', { assignee: { from: null, to: { name: 'Andrés Castro' } } })).text,
    ).toBe('Laura Gómez creó el incidente y lo asignó a Andrés Castro')
  })

  it('dice "se lo asignó" cuando quien crea se asigna a sí mismo', () => {
    const own = activity('created', { assignee: { from: null, to: laura } })

    expect(describeActivity(own).text).toBe('Laura Gómez creó el incidente y se lo asignó')
  })

  it('cuenta la reapertura: quién la pidió, quién la rechazó y a qué estado volvió', () => {
    const status = { from: { name: 'Cerrado' }, to: { name: 'En progreso' } }

    expect(describeActivity(activity('reopen_requested')).text).toBe('Laura Gómez pidió reabrir el incidente')
    expect(describeActivity(activity('reopen_rejected')).text).toBe('Laura Gómez rechazó la reapertura')
    expect(describeActivity(activity('reopened', { status }))).toEqual({
      text: 'Laura Gómez reabrió el incidente',
      detail: 'Cerrado → En progreso',
    })
  })
})
