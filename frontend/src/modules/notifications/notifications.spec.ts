// Centro de notificaciones con la API simulada: textos de cada aviso, a dónde lleva, páginas, marcar como leídas y la
// conversación abierta que se da por leída.
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { ApiError } from '@/shared/http'
import { useNotificationsStore } from './application/useNotificationsStore'
import { badgeText, detail, headline, target, type Notification } from './domain/notification'
import { notificationsApi } from './infrastructure/notificationsApi'

vi.mock('./infrastructure/notificationsApi', () => ({
  notificationsApi: { page: vi.fn(), unread: vi.fn(), markRead: vi.fn(), markAllRead: vi.fn() },
}))

const api = vi.mocked(notificationsApi)

const notice = (changes: Partial<Notification> = {}): Notification => ({
  id: 'N1',
  type: 'comment_received',
  category: 'conversations',
  occurrences: 1,
  actorName: 'Laura Gómez',
  incident: { id: 'I1', code: 'TZ-26-0010', title: 'Facturación lenta' },
  excerpt: '¿Desde qué equipo?',
  status: null,
  commentDeleted: false,
  unassigned: false,
  read: false,
  createdAt: '2026-10-28T12:00:00+00:00',
  ...changes,
})

beforeEach(() => {
  vi.clearAllMocks()
  setActivePinia(createPinia())
})

describe('textos de los avisos', () => {
  it('cuenta qué pasó según el tipo', () => {
    expect(headline(notice())).toBe('Laura Gómez escribió')
    expect(headline(notice({ occurrences: 3 }))).toBe('3 mensajes nuevos, el último de Laura Gómez')
    expect(headline(notice({ type: 'incident_assigned', category: 'activity' }))).toBe(
      'Laura Gómez te asignó este incidente',
    )
    expect(headline(notice({ type: 'status_changed', status: 'Resuelto' }))).toBe('Laura Gómez lo pasó a Resuelto')
    expect(headline(notice({ type: 'status_changed' }))).toBe('Laura Gómez cambió su estado')
    expect(headline(notice({ type: 'incident_reopened', actorName: null }))).toBe('Alguien lo reabrió')
    expect(headline(notice({ type: 'reopen_rejected' }))).toBe('Laura Gómez rechazó reabrirlo')
  })

  it('cuenta los avisos de coordinación, con o sin responsable', () => {
    expect(headline(notice({ type: 'unassigned_created' }))).toBe('Laura Gómez reportó un incidente sin asignar')
    expect(headline(notice({ type: 'critical_created' }))).toBe('Laura Gómez reportó un incidente crítico')
    expect(headline(notice({ type: 'critical_created', unassigned: true }))).toBe(
      'Laura Gómez reportó un incidente crítico sin asignar',
    )
    expect(headline(notice({ type: 'reopen_requested' }))).toBe('Laura Gómez pidió reabrirlo')
    expect(headline(notice({ type: 'incident_overdue', actorName: null }))).toBe('Venció sin resolverse')
    expect(headline(notice({ type: 'incident_overdue', actorName: null, unassigned: true }))).toBe(
      'Venció sin resolverse y sigue sin responsable',
    )
  })

  it('muestra lo que se dijo, salvo si se borró o ya no se ve el incidente', () => {
    expect(detail(notice())).toBe('¿Desde qué equipo?')
    expect(detail(notice({ commentDeleted: true }))).toBe('Mensaje eliminado')
    expect(detail(notice({ incident: null }))).toBeNull()
  })

  it('lleva al incidente, con la conversación abierta si es un mensaje', () => {
    expect(target(notice())).toEqual({ name: 'incident-detail', params: { id: 'I1' }, query: { conversacion: '1' } })
    expect(target(notice({ category: 'activity' }))?.query).toEqual({})
    expect(target(notice({ incident: null }))).toBeNull()
  })

  it('la campana muestra hasta 99', () => {
    expect(badgeText(7)).toBe('7')
    expect(badgeText(120)).toBe('99+')
  })
})

describe('useNotificationsStore', () => {
  it('carga una pestaña, sigue con las anteriores y guarda las sin leer', async () => {
    api.page.mockResolvedValueOnce({ items: [notice()], unread: { conversations: 1, activity: 2 }, before: 'N1' })
    api.page.mockResolvedValueOnce({
      items: [notice({ id: 'N0', read: true })],
      unread: { conversations: 1, activity: 2 },
      before: null,
    })
    const store = useNotificationsStore()

    await store.ensure('conversations')
    await store.ensure('conversations')
    await store.load('conversations', true)

    expect(api.page).toHaveBeenCalledTimes(2)
    expect(api.page).toHaveBeenLastCalledWith('conversations', 'N1')
    expect(store.lists.conversations.items.map((item) => item.id)).toEqual(['N1', 'N0'])
    expect(store.total).toBe(3)
  })

  it('marca como leída una o todas las de una pestaña', async () => {
    api.page.mockResolvedValue({
      items: [notice(), notice({ id: 'N2' })],
      unread: { conversations: 2, activity: 0 },
      before: null,
    })
    api.markRead.mockResolvedValue({ conversations: 1, activity: 0 })
    api.markAllRead.mockResolvedValue({ conversations: 0, activity: 0 })
    const store = useNotificationsStore()
    await store.load('conversations')

    const first = () => store.lists.conversations.items[0] ?? notice({ id: 'falta' })
    await store.markRead(first())
    await store.markRead(first())
    expect(api.markRead).toHaveBeenCalledTimes(1)
    expect(store.unread.conversations).toBe(1)

    await store.markAllRead('conversations')
    expect(store.lists.conversations.items.every((item) => item.read)).toBe(true)
    expect(store.total).toBe(0)
  })

  it('si no se pudo marcar todo, lo dice', async () => {
    api.markAllRead.mockRejectedValueOnce(new ApiError('network', 'NETWORK_ERROR'))
    const store = useNotificationsStore()

    await store.markAllRead('activity')

    expect(store.actionError?.code).toBe('NETWORK_ERROR')
  })

  it('la conversación abierta se da por leída, también lo que llega mientras tanto', async () => {
    api.unread.mockResolvedValue({ conversations: 1, activity: 0 })
    api.markAllRead.mockResolvedValue({ conversations: 0, activity: 0 })
    const store = useNotificationsStore()
    await store.start('U1')

    await store.watchConversation('I1')
    expect(api.markAllRead).toHaveBeenLastCalledWith('conversations', 'I1')

    await store.changed()
    expect(api.markAllRead).toHaveBeenCalledTimes(2)

    await store.watchConversation(null)
    await store.changed()
    expect(api.markAllRead).toHaveBeenCalledTimes(2)
  })

  it('con otra persona en la pestaña empieza de cero', async () => {
    api.unread.mockResolvedValue({ conversations: 0, activity: 0 })
    api.page.mockResolvedValue({ items: [notice()], unread: { conversations: 1, activity: 0 }, before: null })
    const store = useNotificationsStore()
    await store.start('U1')
    await store.load('conversations')

    await store.start('U2')

    expect(store.lists.conversations.items).toEqual([])
    expect(store.lists.conversations.loaded).toBe(false)
  })
})
