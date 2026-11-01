// Pruebas de la conexión de tiempo real con un Echo falso: se conecta un momento después de abrir la app, se suscribe a
// los canales que permiten los permisos (y los ajusta si cambian), publica cada aviso en el bus, avisa al reconectarse
// y se desconecta al salir. Si no hay configuración, la app sigue sin tiempo real. Documentado en docs/frontend/realtime.md
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { useSessionStore } from '@/modules/auth/application/useSessionStore'
import type { SessionUser } from '@/modules/auth/domain/session'
import { withSetup } from '@/shared/testing/withSetup'
import { createEcho, realtimeConfig } from '../infrastructure/echoClient'
import { onRealtime } from './realtimeBus'
import { useRealtimeConnection } from './useRealtimeConnection'

vi.mock('../infrastructure/echoClient', () => ({ createEcho: vi.fn(), realtimeConfig: vi.fn() }))

type Listener = (payload: unknown) => void

// Echo falso: guarda qué canales se abrieron, sus oyentes y el aviso de cambios de conexión
function fakeEcho() {
  const channels = new Map<string, Map<string, Listener>>()
  let onChange: (status: string) => void = () => {}
  return {
    channels,
    connection: (status: string) => onChange(status),
    echo: {
      private: (name: string) => {
        const listeners = new Map<string, Listener>()
        channels.set(name, listeners)
        const channel = {
          listen: (event: string, listener: Listener) => {
            listeners.set(event, listener)
            return channel
          },
        }
        return channel
      },
      leave: vi.fn((name: string) => channels.delete(name)),
      socketId: () => 'socket-1',
      disconnect: vi.fn(),
      connector: { onConnectionChange: (listener: (status: string) => void) => (onChange = listener) },
    },
  }
}

const user: SessionUser = {
  id: 'ME',
  name: 'Laura',
  email: 'laura@trazo.test',
  roles: [],
  permissions: ['incidents.view.any'],
  mfa: { enabled: true, required: true, recoveryCodesLeft: 8 },
}

beforeEach(() => {
  vi.clearAllMocks()
  vi.useFakeTimers()
  vi.mocked(realtimeConfig).mockResolvedValue({ key: 'clave', path: '/ws' })
})

afterEach(() => vi.useRealTimers())

async function connect() {
  const fake = fakeEcho()
  vi.mocked(createEcho).mockReturnValue(fake.echo as never)
  const mounted = await withSetup(() => {
    useSessionStore().user = { ...user }
    return useRealtimeConnection()
  })
  return { fake, ...mounted }
}

describe('conexión de tiempo real', () => {
  it('se conecta un momento después de abrir y se suscribe a los canales permitidos', async () => {
    const { fake } = await connect()
    expect(createEcho).not.toHaveBeenCalled()

    await vi.advanceTimersByTimeAsync(1500)

    expect([...fake.channels.keys()]).toEqual(['users.ME', 'incidents.all'])
  })

  it('publica en el bus los avisos de incidentes, de asignación y de sesión', async () => {
    const { fake } = await connect()
    await vi.advanceTimersByTimeAsync(1500)
    const received: string[] = []
    const stops = [
      onRealtime('incident', ({ id }) => received.push(`incidente ${id}`)),
      onRealtime('assigned', ({ id }) => received.push(`asignado ${id}`)),
      onRealtime('session', () => received.push('sesión')),
    ]

    fake.channels.get('incidents.all')?.get('.incident.changed')?.({ id: 'I1', change: 'updated' })
    fake.channels.get('users.ME')?.get('.incident.assigned')?.({ id: 'I2' })
    fake.channels.get('users.ME')?.get('.session.changed')?.({})
    stops.forEach((stop) => stop())

    expect(received).toEqual(['incidente I1', 'asignado I2', 'sesión'])
  })

  it('si cambian los permisos, sale de los canales que ya no corresponden y entra a los nuevos', async () => {
    const { fake } = await connect()
    await vi.advanceTimersByTimeAsync(1500)

    useSessionStore().user = { ...user, permissions: ['incidents.view.unassigned'] }
    await vi.advanceTimersByTimeAsync(0)

    expect(fake.echo.leave).toHaveBeenCalledWith('incidents.all')
    expect([...fake.channels.keys()]).toEqual(['users.ME', 'incidents.unassigned'])
  })

  it('avisa al reconectarse (no en la primera conexión) y se desconecta al salir', async () => {
    const { fake, unmount } = await connect()
    await vi.advanceTimersByTimeAsync(1500)
    const reconnected = vi.fn()
    const stop = onRealtime('reconnected', reconnected)

    fake.connection('connected')
    fake.connection('unavailable')
    fake.connection('connected')
    stop()
    unmount()

    expect(reconnected).toHaveBeenCalledTimes(1)
    expect(fake.echo.disconnect).toHaveBeenCalled()
  })

  it('sin configuración del tiempo real, la app sigue sin conectarse', async () => {
    vi.mocked(realtimeConfig).mockRejectedValue(new Error('sin red'))
    await connect()

    await vi.advanceTimersByTimeAsync(1500)

    expect(createEcho).not.toHaveBeenCalled()
  })

  it('si se sale antes de conectar, no se conecta', async () => {
    const { unmount } = await connect()

    unmount()
    await vi.advanceTimersByTimeAsync(1500)

    expect(createEcho).not.toHaveBeenCalled()
  })
})
