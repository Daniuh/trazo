// Formulario y detalle de incidentes con la API simulada: idempotencia, vencimiento y conflictos de versión.
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { ApiError } from '@/shared/http'
import type { Incident } from '../domain/incident'
import { incidentsApi } from '../infrastructure/incidentsApi'
import { useIncidentDetail } from './useIncidentDetail'
import { useIncidentForm } from './useIncidentForm'

vi.mock('../infrastructure/incidentsApi', () => ({
  incidentsApi: {
    catalogs: vi.fn(),
    get: vi.fn(),
    create: vi.fn(),
    update: vi.fn(),
    changeStatus: vi.fn(),
    assign: vi.fn(),
    remove: vi.fn(),
    activities: vi.fn(),
  },
}))

const api = vi.mocked(incidentsApi)

const incident: Incident = {
  id: 'I1',
  code: 'INC-000001',
  title: 'Impresora',
  description: 'No imprime',
  priority: { code: 'medium', name: 'Media', level: 2 },
  status: { code: 'open', name: 'Abierto', isFinal: false },
  creator: { id: 'U1', name: 'Juan' },
  assignee: null,
  dueDate: '2020-01-01T10:00:00Z',
  isOverdue: true,
  resolvedAt: null,
  createdAt: '2019-12-30T10:00:00Z',
  updatedAt: '2019-12-30T10:00:00Z',
  deletedAt: null,
  version: 3,
  actions: { update: true, changeStatus: true, take: true, assign: false, delete: false, restore: false },
  nextStatuses: [{ code: 'in_progress', name: 'En progreso' }],
}

beforeEach(() => {
  setActivePinia(createPinia())
  vi.clearAllMocks()
  api.catalogs.mockResolvedValue({
    priorities: [{ code: 'medium', name: 'Media', level: 2, resolutionMinutes: 4320 }],
    statuses: [],
  })
  api.activities.mockResolvedValue({ items: [], total: 0 })
})

describe('useIncidentForm', () => {
  it('no envía nada si faltan datos obligatorios', async () => {
    const form = useIncidentForm(null)
    await form.prepare()

    expect(await form.submit()).toBeNull()
    expect(form.fieldErrors.value).toHaveProperty('title')
    expect(api.create).not.toHaveBeenCalled()
  })

  it('reintentar el mismo envío reutiliza la Idempotency-Key; otros datos usan una nueva', async () => {
    api.create.mockRejectedValue(new ApiError('network', 'NETWORK_ERROR'))
    const form = useIncidentForm(null)
    await form.prepare()
    Object.assign(form.form, { title: 'Sin red', description: 'No hay conexión' })

    await form.submit()
    await form.submit()
    form.form.title = 'Sin red en el piso 3'
    await form.submit()

    const keys = api.create.mock.calls.map((call) => call[1])
    expect(keys[0]).toBe(keys[1])
    expect(keys[2]).not.toBe(keys[0])
  })

  it('al editar, un vencimiento sin tocar no se envía (el incidente ya venció)', async () => {
    api.get.mockResolvedValue(incident)
    api.update.mockResolvedValue(incident)
    const form = useIncidentForm('I1')
    await form.prepare()
    form.form.title = 'Impresora del piso 3'

    await form.submit()

    expect(api.update).toHaveBeenCalledWith(
      'I1',
      3,
      expect.objectContaining({ dueDate: null, title: 'Impresora del piso 3' }),
    )
  })

  it('no deja editar lo que la API marca como no editable', async () => {
    api.get.mockResolvedValue({ ...incident, actions: { ...incident.actions, update: false } })

    expect(await useIncidentForm('I1').prepare()).toBe('not-editable')
  })

  it('un conflicto de versión se marca aparte para ofrecer recargar', async () => {
    api.get.mockResolvedValue(incident)
    api.update.mockRejectedValue(new ApiError('http', 'VERSION_CONFLICT', { status: 409 }))
    const form = useIncidentForm('I1')
    await form.prepare()

    await form.submit()

    expect(form.conflict.value).toBe(true)
    expect(form.formError.value).toBeNull()
  })
})

describe('useIncidentDetail', () => {
  it('envía la versión conocida y se queda con la nueva', async () => {
    api.get.mockResolvedValue(incident)
    api.changeStatus.mockResolvedValue({ ...incident, version: 4 })
    const detail = useIncidentDetail('I1')
    await detail.load()

    expect(await detail.changeStatus('in_progress')).toBe(true)
    expect(api.changeStatus).toHaveBeenCalledWith('I1', 3, 'in_progress')
    expect(detail.incident.value?.version).toBe(4)
  })

  it('separa el conflicto de versión de los demás errores', async () => {
    api.get.mockResolvedValue(incident)
    api.assign.mockRejectedValueOnce(new ApiError('http', 'VERSION_CONFLICT', { status: 409 }))
    api.assign.mockRejectedValueOnce(new ApiError('http', 'INCIDENT_ALREADY_ASSIGNED', { status: 409 }))
    const detail = useIncidentDetail('I1')
    await detail.load()

    await detail.assign('U2')
    expect(detail.conflict.value).toBe(true)

    await detail.assign('U2')
    expect(detail.conflict.value).toBe(false)
    expect(detail.actionError.value?.code).toBe('INCIDENT_ALREADY_ASSIGNED')
  })
})
