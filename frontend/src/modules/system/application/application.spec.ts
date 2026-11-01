// Pruebas de la capa de aplicación de la pantalla de sistema con la API simulada: el panel (con su actualización cada
// 30 s solo con la pestaña visible), los jobs fallidos y la lista de eventos con su cambio de estado.
// Documentado en docs/frontend/system.md
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { ApiError } from '@/shared/http'
import { flush, withSetup } from '@/shared/testing/withSetup'
import type { Health, SystemEvent } from '../domain/system'
import { systemApi } from '../infrastructure/systemApi'
import { HEALTH_REFRESH_MS, useFailedJobs, useHealth } from './useHealth'
import { useSystemEvents } from './useSystemEvents'

vi.mock('../infrastructure/systemApi', () => ({
  systemApi: { health: vi.fn(), failedJobs: vi.fn(), events: vi.fn(), eventTypes: vi.fn(), changeStatus: vi.fn() },
}))

const api = vi.mocked(systemApi)
const health: Health = {
  checkedAt: '2026-10-25T12:00:00Z',
  status: 'ok',
  components: [],
  maintenance: null,
  app: { environment: 'local', laravel: '13', php: '8.4' },
}
const event: SystemEvent = {
  id: 1,
  type: { code: 'job_failed', name: 'Job fallido', category: 'failure', severity: 'warning' },
  message: 'Falló',
  context: null,
  occurrences: 2,
  firstSeenAt: '2026-10-25T10:00:00Z',
  lastSeenAt: '2026-10-25T11:00:00Z',
  traceId: null,
  status: 'open',
  statusChangedBy: null,
  statusChangedAt: null,
}

beforeEach(() => {
  vi.clearAllMocks()
  api.health.mockResolvedValue(health)
  api.events.mockResolvedValue({ items: [event], next: null, meta: null })
  api.eventTypes.mockResolvedValue([event.type])
})

afterEach(() => vi.useRealTimers())

describe('panel de salud', () => {
  it('se pide al abrir y se actualiza cada 30 s solo con la pestaña visible', async () => {
    vi.useFakeTimers()
    const { result } = await withSetup(() => useHealth())
    await vi.advanceTimersByTimeAsync(0)
    expect(result.health.value).toEqual(health)

    await vi.advanceTimersByTimeAsync(HEALTH_REFRESH_MS)
    expect(api.health).toHaveBeenCalledTimes(2)

    const visibility = vi.spyOn(document, 'visibilityState', 'get').mockReturnValue('hidden')
    await vi.advanceTimersByTimeAsync(HEALTH_REFRESH_MS)
    expect(api.health).toHaveBeenCalledTimes(2)
    visibility.mockRestore()
  })

  it('guarda el error si la API no responde', async () => {
    api.health.mockRejectedValue(new ApiError('network', 'NETWORK_ERROR'))
    const { result } = await withSetup(() => useHealth())
    await flush()

    expect(result.error.value?.code).toBe('NETWORK_ERROR')
    expect(result.loading.value).toBe(false)
  })
})

describe('jobs fallidos', () => {
  it('trae los más recientes con el total, o el error', async () => {
    api.failedJobs
      .mockResolvedValueOnce({
        items: [{ id: 'J1', job: 'SendMail', queue: 'default', failedAt: '', error: '' }],
        total: 12,
      })
      .mockRejectedValueOnce(new ApiError('http', 'FORBIDDEN_ACTION', { status: 403 }))
    const jobs = useFailedJobs()

    await jobs.load()
    expect(jobs.total.value).toBe(12)
    await jobs.load()
    expect(jobs.error.value?.code).toBe('FORBIDDEN_ACTION')
  })
})

describe('eventos', () => {
  it('pide los eventos con los criterios de la URL y el catálogo de tipos', async () => {
    const { result } = await withSetup(() => useSystemEvents(), '/sistema/eventos?severity=critical')
    await flush()

    expect(api.events).toHaveBeenCalledWith(expect.objectContaining({ severity: 'critical' }), null, expect.anything())
    expect(result.types.value).toEqual([event.type])
  })

  it('al cambiar el estado reemplaza la fila; si falla devuelve el error', async () => {
    api.changeStatus
      .mockResolvedValueOnce({ ...event, status: 'acknowledged' })
      .mockRejectedValueOnce(new ApiError('http', 'SYSTEM_EVENT_NOT_FOUND', { status: 404 }))
    const { result } = await withSetup(() => useSystemEvents())
    await flush()

    expect(await result.changeStatus(1, 'acknowledged')).toBeNull()
    expect(result.items.value[0]?.status).toBe('acknowledged')
    expect((await result.changeStatus(1, 'resolved'))?.code).toBe('SYSTEM_EVENT_NOT_FOUND')
  })

  it('sin catálogo de tipos la lista funciona igual', async () => {
    api.eventTypes.mockRejectedValue(new ApiError('network', 'NETWORK_ERROR'))
    const { result } = await withSetup(() => useSystemEvents())
    await flush()

    expect(result.types.value).toEqual([])
    expect(result.items.value).toHaveLength(1)
  })
})
