// Pruebas de la capa de aplicación del dashboard con la API simulada: carga del diseño y las cifras, cifras del
// borrador al personalizar, refresco en vivo (como máximo cada 10 s y nunca al personalizar) y el editor.
// Documentado en docs/frontend/dashboard.md
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { ref } from 'vue'
import { emitRealtime } from '@/modules/realtime/application/realtimeBus'
import { ApiError } from '@/shared/http'
import { flush, withSetup } from '@/shared/testing/withSetup'
import type { DashboardLayout, DashboardWidget } from '../domain/dashboard'
import { dashboardApi } from '../infrastructure/dashboardApi'
import { useDashboard } from './useDashboard'
import { useDashboardEditor } from './useDashboardEditor'

vi.mock('../infrastructure/dashboardApi', () => ({
  dashboardApi: { layout: vi.fn(), metrics: vi.fn(), save: vi.fn(), reset: vi.fn() },
}))

const api = vi.mocked(dashboardApi)

function widget(code: DashboardWidget['code'], x: number, visible = true): DashboardWidget {
  return {
    code,
    name: code,
    description: '',
    kind: 'kpi',
    limits: { minW: 2, maxW: 6, minH: 1, maxH: 2 },
    visible,
    x,
    y: 0,
    w: 3,
    h: 1,
  }
}

const layout: DashboardLayout = {
  scope: 'general',
  customized: false,
  columns: 12,
  widgets: [widget('total_incidents', 0), widget('pending_incidents', 3), widget('overdue_incidents', 6, false)],
}

beforeEach(() => {
  vi.clearAllMocks()
  api.layout.mockResolvedValue(layout)
  api.metrics.mockResolvedValue({ metrics: {}, generatedAt: new Date().toISOString() })
})

afterEach(() => vi.useRealTimers())

describe('useDashboard', () => {
  it('al abrir pide el diseño y las cifras de los widgets visibles', async () => {
    const { result } = await withSetup(() => useDashboard())
    await flush()

    expect(result.widgets.value.map((item) => item.code)).toEqual(['total_incidents', 'pending_incidents'])
    expect(api.metrics).toHaveBeenCalledWith(['total_incidents', 'pending_incidents'], 30, expect.anything())
    expect(result.loading.value).toBe(false)
  })

  it('guarda el error del diseño y el de las cifras por separado', async () => {
    api.layout.mockRejectedValueOnce(new ApiError('network', 'NETWORK_ERROR'))
    const { result } = await withSetup(() => useDashboard())
    await flush()
    expect(result.error.value?.code).toBe('NETWORK_ERROR')

    api.metrics.mockRejectedValueOnce(new ApiError('http', 'INTERNAL_ERROR', { status: 500 }))
    await result.load()
    expect(result.metricsError.value?.code).toBe('INTERNAL_ERROR')
  })

  it('al personalizar pide las cifras de un widget nuevo del borrador; al aplicar vuelve al diseño guardado', async () => {
    const { result } = await withSetup(() => useDashboard())
    await flush()
    api.metrics.mockClear()

    result.followDraft(['total_incidents', 'overdue_incidents'])
    await flush()
    expect(api.metrics).toHaveBeenCalledWith(['total_incidents', 'overdue_incidents'], 30, expect.anything())

    result.applyLayout({ ...layout, customized: true })
    await flush()
    expect(result.layout.value?.customized).toBe(true)
  })

  it('un cambio en vivo refresca las cifras como máximo una vez cada 10 segundos, nunca al personalizar', async () => {
    vi.useFakeTimers()
    const { result } = await withSetup(() => useDashboard())
    await vi.runOnlyPendingTimersAsync()
    api.metrics.mockClear()

    emitRealtime('incident', { id: 'I1', change: 'updated' })
    emitRealtime('incident', { id: 'I2', change: 'updated' })
    await vi.advanceTimersByTimeAsync(0)
    expect(api.metrics).toHaveBeenCalledTimes(1)

    emitRealtime('incident', { id: 'I3', change: 'updated' })
    await vi.advanceTimersByTimeAsync(5_000)
    expect(api.metrics).toHaveBeenCalledTimes(1)
    await vi.advanceTimersByTimeAsync(5_000)
    expect(api.metrics).toHaveBeenCalledTimes(2)

    // Al personalizar, las cifras siguen al borrador y los avisos en vivo no refrescan
    result.followDraft(['total_incidents'])
    await vi.advanceTimersByTimeAsync(0)
    api.metrics.mockClear()
    emitRealtime('reconnected', {})
    await vi.advanceTimersByTimeAsync(20_000)
    expect(api.metrics).not.toHaveBeenCalled()
  })

  it('al volver a la pestaña refresca si las cifras tienen más de un minuto', async () => {
    api.metrics.mockResolvedValue({ metrics: {}, generatedAt: '2020-01-01T00:00:00Z' })
    await withSetup(() => useDashboard())
    await flush()
    api.metrics.mockClear()

    document.dispatchEvent(new Event('visibilitychange'))
    await flush()

    expect(api.metrics).toHaveBeenCalledTimes(1)
  })
})

describe('useDashboardEditor', () => {
  it('edita un borrador sin tocar lo guardado y sabe si hay cambios', () => {
    const editor = useDashboardEditor(ref(layout), vi.fn())

    editor.start()
    expect(editor.editing.value).toBe(true)
    expect(editor.dirty.value).toBe(false)
    expect(editor.hidden.value.map((item) => item.code)).toEqual(['overdue_incidents'])

    expect(editor.canMove('pending_incidents', 'left')).toBe(true)
    editor.move('pending_incidents', 'left')
    expect(editor.dirty.value).toBe(true)
    editor.resize('total_incidents', 'w', 1)
    editor.add('overdue_incidents')
    editor.remove('total_incidents')
    editor.place([{ i: 'pending_incidents', x: 0, y: 0, w: 3, h: 1 }])
    expect(editor.canResize('pending_incidents', 'h', 1)).toBe(true)
    expect(layout.widgets[0]?.visible).toBe(true)

    editor.cancel()
    expect(editor.editing.value).toBe(false)
  })

  it('guardar y restaurar aplican el diseño que devuelve el servidor; un error deja seguir editando', async () => {
    const applied = vi.fn()
    const editor = useDashboardEditor(ref(layout), applied)
    api.save
      .mockResolvedValueOnce({ ...layout, customized: true })
      .mockRejectedValueOnce(new ApiError('http', 'WIDGETS_OVERLAP', { status: 422 }))
    api.reset.mockResolvedValue(layout)

    editor.start()
    expect(await editor.save()).toBe(true)
    expect(applied).toHaveBeenCalledWith(expect.objectContaining({ customized: true }))
    expect(editor.editing.value).toBe(false)

    editor.start()
    expect(await editor.save()).toBe(false)
    expect(editor.error.value?.code).toBe('WIDGETS_OVERLAP')
    expect(editor.editing.value).toBe(true)

    expect(await editor.restore()).toBe(true)
  })

  it('sin diseño cargado no entra a editar', () => {
    const editor = useDashboardEditor(ref(null), vi.fn())

    editor.start()

    expect(editor.editing.value).toBe(false)
    expect(editor.columns.value).toBe(12)
  })
})
