// "Mis incidentes" con la API simulada: columnas por estado, a dónde se puede mover una tarjeta, orden de la lista,
// la vista elegida y mover con conflicto de versión.
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { ApiError } from '@/shared/http'
import { boardColumns, canMoveTo, parseView, sortBoardItems } from '../domain/board'
import type { Incident, StatusOption } from '../domain/incident'
import { boardApi } from '../infrastructure/boardApi'
import { incidentsApi } from '../infrastructure/incidentsApi'
import { useMyIncidents } from './useMyIncidents'

vi.mock('../infrastructure/boardApi', () => ({ boardApi: { mine: vi.fn() } }))
vi.mock('../infrastructure/incidentsApi', () => ({ incidentsApi: { changeStatus: vi.fn() } }))

const board = vi.mocked(boardApi)
const api = vi.mocked(incidentsApi)

const status = (code: string, isFinal = false): StatusOption => ({
  code,
  name: code,
  isInitial: code === 'open',
  isPending: !isFinal,
  isFinal,
  requiresNote: false,
  next: [],
})

const card = (id: string, changes: Partial<Incident> = {}): Incident => ({
  id,
  code: `TZ-26-000${id}`,
  title: `Incidente ${id}`,
  description: '',
  priority: { code: 'medium', name: 'Media', level: 2 },
  status: { code: 'open', name: 'Abierto', isFinal: false },
  creator: { id: 'U1', name: 'Juan' },
  assignee: { id: 'U2', name: 'Laura' },
  dueDate: '2026-10-30T10:00:00Z',
  isOverdue: false,
  deadline: { bucket: 'later', timeUsed: 10, resolvedOnTime: null },
  resolvedAt: null,
  createdAt: '2026-10-20T10:00:00Z',
  updatedAt: '2026-10-20T10:00:00Z',
  deletedAt: null,
  version: 1,
  actions: {
    update: true,
    changeStatus: true,
    take: false,
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
  commentsCount: null,
  ...changes,
})

beforeEach(() => vi.clearAllMocks())

describe('tablero', () => {
  it('arma una columna por estado, en el orden del catálogo', () => {
    const progress = card('2', { status: { code: 'in_progress', name: 'En progreso', isFinal: false } })
    const columns = boardColumns([status('open'), status('in_progress'), status('closed', true)], [card('1'), progress])

    expect(columns.map((column) => [column.status.code, column.items.map((item) => item.id)])).toEqual([
      ['open', ['1']],
      ['in_progress', ['2']],
      ['closed', []],
    ])
    expect(columns[2]?.status.isFinal).toBe(true)
  })

  it('solo se mueve a los estados que dice la API', () => {
    expect(canMoveTo(card('1'), 'in_progress')).toBe(true)
    expect(canMoveTo(card('1'), 'closed')).toBe(false)
  })

  it('ordena la lista por cualquier columna, con el id como desempate', () => {
    const items = [
      card('1', { priority: { code: 'low', name: 'Baja', level: 1 }, dueDate: '2026-11-02T00:00:00Z' }),
      card('2', { priority: { code: 'critical', name: 'Crítica', level: 4 }, dueDate: '2026-11-01T00:00:00Z' }),
      card('3', { status: { code: 'in_progress', name: 'En progreso', isFinal: false } }),
    ]
    const order = ['open', 'in_progress']

    expect(sortBoardItems(items, 'due_date', false, order).map((item) => item.id)).toEqual(['3', '2', '1'])
    expect(sortBoardItems(items, 'priority', true, order).map((item) => item.id)).toEqual(['2', '3', '1'])
    expect(sortBoardItems(items, 'status', false, order).map((item) => item.id)).toEqual(['1', '2', '3'])
    expect(sortBoardItems(items, 'code', true, order)[0]?.id).toBe('3')
  })

  it('reconoce solo las vistas que existen', () => {
    expect(parseView('lista')).toBe('lista')
    expect(parseView('tabla')).toBeNull()
    expect(parseView(undefined)).toBeNull()
  })
})

describe('useMyIncidents', () => {
  it('mueve una tarjeta y la reemplaza por lo que devuelve la API', async () => {
    board.mine.mockResolvedValue({ items: [card('1'), card('2')], truncated: false, limit: 500, finishedDays: 7 })
    const moved = card('1', { status: { code: 'in_progress', name: 'En progreso', isFinal: false }, version: 2 })
    api.changeStatus.mockResolvedValue(moved)
    const mine = useMyIncidents()
    await mine.load()

    expect(await mine.move(card('1'), 'in_progress', null)).toBe(true)

    expect(api.changeStatus).toHaveBeenCalledWith('1', 1, 'in_progress', null)
    expect(mine.board.value?.items.map((item) => item.status.code)).toEqual(['in_progress', 'open'])
    expect(mine.moving.value).toBeNull()
  })

  it('distingue el conflicto de versión de los demás errores', async () => {
    const mine = useMyIncidents()
    api.changeStatus.mockRejectedValueOnce(new ApiError('http', 'VERSION_CONFLICT'))
    expect(await mine.move(card('1'), 'in_progress', null)).toBe(false)
    expect(mine.conflict.value).toBe(true)
    expect(mine.actionError.value).toBeNull()

    api.changeStatus.mockRejectedValueOnce(new ApiError('http', 'STATUS_NOTE_REQUIRED'))
    await mine.move(card('1'), 'resolved', null)
    expect(mine.conflict.value).toBe(false)
    expect(mine.actionError.value?.code).toBe('STATUS_NOTE_REQUIRED')

    mine.clearError()
    expect(mine.actionError.value).toBeNull()
  })

  it('si falla la carga sin tablero previo, guarda el error para mostrarlo', async () => {
    board.mine.mockRejectedValueOnce(new ApiError('network', 'NETWORK_ERROR'))
    const mine = useMyIncidents()

    await mine.load()

    expect(mine.loadError.value?.code).toBe('NETWORK_ERROR')
    expect(mine.loading.value).toBe(false)
  })
})
