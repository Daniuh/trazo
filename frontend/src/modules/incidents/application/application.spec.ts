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
    requestReopen: vi.fn(),
    reopen: vi.fn(),
    approveReopen: vi.fn(),
    rejectReopen: vi.fn(),
  },
}))

const api = vi.mocked(incidentsApi)

const incident: Incident = {
  id: 'I1',
  code: 'TZ-26-0001',
  title: 'Impresora',
  description: 'No imprime',
  priority: { code: 'medium', name: 'Media', level: 2 },
  status: { code: 'open', name: 'Abierto', isFinal: false },
  creator: { id: 'U1', name: 'Juan' },
  assignee: null,
  dueDate: '2020-01-01T10:00:00Z',
  isOverdue: true,
  deadline: { bucket: 'overdue', timeUsed: 100, resolvedOnTime: null },
  resolvedAt: null,
  createdAt: '2019-12-30T10:00:00Z',
  updatedAt: '2019-12-30T10:00:00Z',
  deletedAt: null,
  version: 3,
  actions: {
    update: true,
    changeStatus: true,
    take: true,
    assign: false,
    delete: false,
    restore: false,
    requestReopen: false,
    reopen: false,
    decideReopen: false,
    comment: true,
  },
  nextStatuses: [{ code: 'in_progress', name: 'En progreso', requiresNote: false }],
  reopenRequested: false,
  reopenRequest: null,
  commentsCount: 0,
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

  it('avisa antes de enviar si el título es muy corto o muy largo', async () => {
    const form = useIncidentForm(null)
    await form.prepare()
    Object.assign(form.form, { title: '  Red  ', description: 'No hay conexión' })

    expect(await form.submit()).toBeNull()
    expect(form.fieldErrors.value.title).toContain('5')

    form.form.title = 'a'.repeat(81)
    expect(await form.submit()).toBeNull()
    expect(form.fieldErrors.value.title).toContain('80')
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

    expect(await detail.changeStatus('in_progress', 'Empiezo hoy')).toBe(true)
    expect(api.changeStatus).toHaveBeenCalledWith('I1', 3, 'in_progress', 'Empiezo hoy')
    expect(detail.incident.value?.version).toBe(4)
  })

  it('pide, aprueba y rechaza la reapertura con la versión y el motivo', async () => {
    api.get.mockResolvedValue(incident)
    api.requestReopen.mockResolvedValue(incident)
    api.approveReopen.mockResolvedValue({ ...incident, version: 4 })
    api.rejectReopen.mockResolvedValue(incident)
    api.reopen.mockResolvedValue(incident)
    const detail = useIncidentDetail('I1')
    await detail.load()

    await detail.requestReopen('Volvió a fallar')
    await detail.rejectReopen('Es otro problema')
    await detail.reopen('Me equivoqué al cerrarlo')
    await detail.approveReopen()

    expect(api.requestReopen).toHaveBeenCalledWith('I1', 'Volvió a fallar')
    expect(api.rejectReopen).toHaveBeenCalledWith('I1', 'Es otro problema')
    expect(api.reopen).toHaveBeenCalledWith('I1', 3, 'Me equivoqué al cerrarlo')
    expect(api.approveReopen).toHaveBeenCalledWith('I1', 3)
    expect(detail.incident.value?.version).toBe(4)
  })

  it('trae el resto del historial por páginas hasta tenerlo completo', async () => {
    const entry = { type: 'updated', user: { id: 'U1', name: 'Juan' }, changes: null, note: null, createdAt: '' }
    api.get.mockResolvedValue(incident)
    api.activities.mockResolvedValueOnce({ items: Array(50).fill(entry), total: 60 })
    api.activities.mockResolvedValueOnce({ items: Array(10).fill(entry), total: 60 })
    const detail = useIncidentDetail('I1')
    await detail.load()

    await detail.loadAllActivities()

    expect(api.activities).toHaveBeenLastCalledWith('I1', 2)
    expect(detail.activities.value).toHaveLength(60)
    expect(detail.activityTotal.value).toBe(60)
  })

  it('un error cerrado en un diálogo se puede descartar', async () => {
    api.get.mockResolvedValue(incident)
    api.requestReopen.mockRejectedValueOnce(new ApiError('http', 'REOPEN_ALREADY_REQUESTED', { status: 409 }))
    const detail = useIncidentDetail('I1')
    await detail.load()

    expect(await detail.requestReopen('x')).toBe(false)
    expect(detail.actionError.value?.code).toBe('REOPEN_ALREADY_REQUESTED')
    detail.clearError()
    expect(detail.actionError.value).toBeNull()
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
