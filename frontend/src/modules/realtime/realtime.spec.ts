// Pruebas del tiempo real en el front: canales según los permisos y el bus de avisos.
// Documentado en docs/frontend/realtime.md
import { describe, expect, it, vi } from 'vitest'
import { emitRealtime, onRealtime } from './application/realtimeBus'
import { channelsFor } from './domain/realtime'

describe('canales', () => {
  it('cada persona escucha su canal y los de incidentes que sus permisos permiten', () => {
    const can = (granted: string[]) => (permission: string) => granted.includes(permission)

    expect(channelsFor('u1', can([]))).toEqual(['users.u1'])
    expect(channelsFor('u1', can(['incidents.view.unassigned']))).toEqual(['users.u1', 'incidents.unassigned'])
    expect(channelsFor('u1', can(['incidents.view.any', 'incidents.view.unassigned']))).toEqual([
      'users.u1',
      'incidents.all',
      'incidents.unassigned',
    ])
  })
})

describe('bus de avisos', () => {
  it('entrega cada aviso a quien lo escucha y deja de hacerlo al cancelar', () => {
    const listener = vi.fn()
    const stop = onRealtime('incident', listener)

    emitRealtime('incident', { id: 'i1', change: 'updated' })
    emitRealtime('assigned', { id: 'i1' })
    stop()
    emitRealtime('incident', { id: 'i2', change: 'deleted' })

    expect(listener).toHaveBeenCalledTimes(1)
    expect(listener).toHaveBeenCalledWith({ id: 'i1', change: 'updated' })
  })
})
