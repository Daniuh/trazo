// Pruebas del listado de incidentes con la API simulada: restaurar, contar un borrador de filtros, exportar, las
// opciones de los filtros según los permisos, los catálogos compartidos y eliminar desde el detalle.
// Documentado en docs/frontend/incidents.md
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { useSessionStore } from '@/modules/auth/application/useSessionStore'
import type { SessionUser } from '@/modules/auth/domain/session'
import { saveFile } from '@/shared/download/saveFile'
import { ApiError } from '@/shared/http'
import { flush, withSetup } from '@/shared/testing/withSetup'
import { DEFAULT_CRITERIA, UNASSIGNED } from '../domain/listing'
import { incidentsApi } from '../infrastructure/incidentsApi'
import { listApi } from '../infrastructure/listApi'
import { useAssignees } from './useAssignees'
import { useCatalogStore } from './useCatalogStore'
import { useIncidentDetail } from './useIncidentDetail'
import { useIncidentList } from './useIncidentList'
import { useListFilterOptions } from './useListFilterOptions'

vi.mock('../infrastructure/listApi', () => ({ listApi: { list: vi.fn(), restore: vi.fn(), export: vi.fn() } }))
vi.mock('../infrastructure/incidentsApi', () => ({
  incidentsApi: { catalogs: vi.fn(), assignees: vi.fn(), remove: vi.fn() },
}))
vi.mock('@/shared/download/saveFile', () => ({ saveFile: vi.fn() }))

const list = vi.mocked(listApi)
const incidents = vi.mocked(incidentsApi)
const page = { items: [], total: 34, lastPage: 2 }

function sessionWith(...permissions: string[]): void {
  const user: SessionUser = {
    id: 'ME',
    name: 'Laura',
    email: 'laura@trazo.test',
    roles: [],
    permissions,
    mfa: { enabled: true, required: true, recoveryCodesLeft: 8 },
  }
  useSessionStore().user = user
}

beforeEach(() => {
  vi.clearAllMocks()
  list.list.mockResolvedValue(page)
  incidents.catalogs.mockResolvedValue({
    priorities: [{ code: 'high', name: 'Alta', level: 3, resolutionMinutes: 1440 }],
    statuses: [
      { code: 'open', name: 'Abierto', isFinal: false },
      { code: 'closed', name: 'Cerrado', isFinal: true },
    ],
  } as never)
  incidents.assignees.mockResolvedValue([{ id: 'P1', name: 'Pedro' }])
})

describe('useIncidentList', () => {
  it('restaura y vuelve a pedir la página; devuelve el error si no se pudo', async () => {
    const { result } = await withSetup(() => useIncidentList(), '/incidentes?deleted=1')
    await flush()
    list.restore.mockResolvedValueOnce(undefined).mockRejectedValueOnce(new ApiError('http', 'INCIDENT_NOT_DELETED'))

    expect(await result.restore('I1')).toBeNull()
    expect((await result.restore('I1'))?.code).toBe('INCIDENT_NOT_DELETED')
  })

  it('cuenta cuántos daría un borrador sin cambiar el listado; si falla, no muestra número', async () => {
    const { result } = await withSetup(() => useIncidentList())
    list.list.mockResolvedValueOnce(page).mockRejectedValueOnce(new ApiError('network', 'NETWORK_ERROR'))

    expect(await result.countFor({ ...DEFAULT_CRITERIA, overdue: true })).toBe(34)
    expect(list.list).toHaveBeenLastCalledWith(expect.objectContaining({ overdue: true, page: 1, perPage: 1 }))
    expect(await result.countFor(DEFAULT_CRITERIA)).toBeNull()
  })

  it('exporta lo filtrado y guarda el archivo; un error queda para la pantalla', async () => {
    const file = new Blob(['csv'])
    list.export
      .mockResolvedValueOnce({ file, filename: 'incidentes.csv' })
      .mockRejectedValueOnce(new ApiError('http', 'EXPORT_TOO_LARGE', { status: 422 }))
    const { result } = await withSetup(() => useIncidentList(), '/incidentes?status=open')

    expect(await result.exportCsv()).toBeNull()
    expect(list.export).toHaveBeenCalledWith(expect.objectContaining({ statuses: ['open'] }))
    expect(saveFile).toHaveBeenCalledWith(file, 'incidentes.csv')
    expect((await result.exportCsv())?.code).toBe('EXPORT_TOO_LARGE')
    expect(result.exporting.value).toBe(false)
  })
})

describe('useListFilterOptions', () => {
  it('quien asigna elige entre todas las personas y "Sin asignar"', async () => {
    const { result } = await withSetup(() => {
      sessionWith('incidents.assign', 'incidents.restore')
      return useListFilterOptions()
    })
    await flush()

    expect(result.assigneeOptions.value.map((option) => option.value)).toEqual([UNASSIGNED, 'P1'])
    expect(result.statuses.value).toHaveLength(2)
    expect(result.priorities.value).toEqual([{ label: 'Alta', value: 'high' }])
    expect(result.canSeeDeleted.value).toBe(true)
  })

  it('un agente elige entre "Asignados a mí" y "Sin asignar"; un solicitante no filtra por asignado', async () => {
    const agent = await withSetup(() => {
      sessionWith('incidents.view.assigned', 'incidents.view.unassigned')
      return useListFilterOptions()
    })
    expect(agent.result.assigneeOptions.value.map((option) => option.value)).toEqual(['ME', UNASSIGNED])
    expect(incidents.assignees).not.toHaveBeenCalled()

    const requester = await withSetup(() => {
      sessionWith('incidents.view.own')
      return useListFilterOptions()
    })
    expect(requester.result.assigneeOptions.value).toEqual([])
  })
})

describe('catálogos y personas', () => {
  it('los catálogos se piden una vez aunque varias pantallas los pidan a la vez', async () => {
    await withSetup(() => null)
    const store = useCatalogStore()

    await Promise.all([store.load(), store.load()])
    await store.load()

    expect(incidents.catalogs).toHaveBeenCalledTimes(1)
    expect(store.priority('high')?.name).toBe('Alta')
    expect(store.priority('nada')).toBeNull()
    expect(store.isFinalStatus('closed')).toBe(true)
    expect(store.isFinalStatus('open')).toBe(false)
  })

  it('las personas asignables guardan el error si la API falla', async () => {
    incidents.assignees.mockRejectedValue(new ApiError('http', 'FORBIDDEN_ACTION', { status: 403 }))
    const assignees = useAssignees()

    await assignees.load()

    expect(assignees.error.value?.code).toBe('FORBIDDEN_ACTION')
    expect(assignees.loading.value).toBe(false)
  })
})

describe('eliminar desde el detalle', () => {
  it('avisa si se eliminó o deja el error a la vista', async () => {
    await withSetup(() => null)
    incidents.remove.mockResolvedValueOnce(undefined).mockRejectedValueOnce(new ApiError('http', 'FORBIDDEN_ACTION'))
    const detail = useIncidentDetail('I1')

    expect(await detail.remove()).toBe(true)
    expect(await detail.remove()).toBe(false)
    expect(detail.actionError.value?.code).toBe('FORBIDDEN_ACTION')
  })
})
