// Detalle: camino de estados, siguiente paso propuesto y actividad unificada.
import { describe, expect, it } from 'vitest'
import { activityFeed } from '../domain/activityFeed'
import type { Activity, Comment, NextStatus, StatusOption } from '../domain/incident'
import { nextStep, statusPath } from '../domain/workflow'

const status = (code: string, name: string, flags: Partial<StatusOption>, next: string[]): StatusOption => ({
  code,
  name,
  isInitial: false,
  isPending: false,
  isFinal: false,
  requiresNote: false,
  next,
  ...flags,
})

// El catálogo de Trazo, en su orden
const CATALOG: StatusOption[] = [
  status('open', 'Abierto', { isInitial: true, isPending: true }, ['in_progress', 'cancelled']),
  status('in_progress', 'En progreso', { isPending: true }, ['on_hold', 'resolved', 'cancelled']),
  status('on_hold', 'En espera', { isPending: true }, ['in_progress', 'cancelled']),
  status('resolved', 'Resuelto', { requiresNote: true }, ['closed', 'in_progress']),
  status('closed', 'Cerrado', { isFinal: true }, []),
  status('cancelled', 'Cancelado', { isFinal: true, requiresNote: true }, []),
]

const allowed = (code: string): NextStatus[] =>
  (CATALOG.find((item) => item.code === code)?.next ?? []).map((next) => {
    const item = CATALOG.find((option) => option.code === next)
    return { code: next, name: item?.name ?? '', requiresNote: item?.requiresNote ?? false }
  })

describe('statusPath', () => {
  it('muestra el camino sin las salidas, con lo hecho y lo actual', () => {
    const path = statusPath(CATALOG, 'on_hold')

    expect(path.steps.map((step) => [step.code, step.state])).toEqual([
      ['open', 'done'],
      ['in_progress', 'done'],
      ['on_hold', 'current'],
      ['resolved', 'todo'],
      ['closed', 'todo'],
    ])
    expect(path.exit).toBeNull()
  })

  it('un cancelado dice que salió del camino', () => {
    expect(statusPath(CATALOG, 'cancelled').exit).toEqual({ code: 'cancelled', name: 'Cancelado' })
  })
})

describe('nextStep', () => {
  it('avanza prefiriendo cerrar el trabajo, nunca por una salida', () => {
    expect(
      ['open', 'in_progress', 'on_hold', 'resolved'].map((code) => nextStep(CATALOG, code, allowed(code))?.code),
    ).toEqual(['in_progress', 'resolved', 'in_progress', 'closed'])
  })

  it('sin permiso para cambiar de estado, o si solo queda cancelar, no propone nada', () => {
    expect(nextStep(CATALOG, 'open', [])).toBeNull()
    expect(nextStep(CATALOG, 'open', [{ code: 'cancelled', name: 'Cancelado', requiresNote: true }])).toBeNull()
  })
})

describe('activityFeed', () => {
  const person = { id: 'U1', name: 'Ana' }
  const change = (at: string): Activity => ({ type: 'created', user: person, changes: null, note: null, createdAt: at })
  const message = (id: string, at: string): Comment => ({
    id,
    author: person,
    body: 'Hola',
    mine: false,
    createdAt: at,
    deletedAt: null,
    canDelete: false,
  })

  it('une cambios y mensajes, del más nuevo al más viejo, y filtra', () => {
    const activities = [change('2026-10-30T10:00:00+00:00')]
    const comments = [message('M1', '2026-10-30T09:00:00+00:00'), message('M2', '2026-10-30T11:00:00+00:00')]

    expect(activityFeed(activities, comments).map((entry) => entry.key)).toEqual([
      'M2',
      'change-2026-10-30T10:00:00+00:00-0',
      'M1',
    ])
    expect(activityFeed(activities, comments, 'messages').map((entry) => entry.kind)).toEqual(['message', 'message'])
    expect(activityFeed(activities, comments, 'changes').map((entry) => entry.kind)).toEqual(['change'])
  })
})
