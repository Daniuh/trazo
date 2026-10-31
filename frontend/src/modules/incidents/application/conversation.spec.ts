// Conversación de un incidente con la API simulada: orden, mensajes anteriores, escribir, borrar y avisos que llegan.
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { ApiError } from '@/shared/http'
import type { Comment } from '../domain/incident'
import { conversationApi } from '../infrastructure/conversationApi'
import { mergeComments, useConversation } from './useConversation'

vi.mock('../infrastructure/conversationApi', () => ({
  conversationApi: { page: vi.fn(), write: vi.fn(), remove: vi.fn() },
}))

const api = vi.mocked(conversationApi)

const message = (id: string, body: string | null = `Mensaje ${id}`): Comment => ({
  id,
  author: { id: 'U1', name: 'Juan' },
  body,
  mine: true,
  createdAt: '2026-10-28T12:00:00+00:00',
  deletedAt: body === null ? '2026-10-28T12:01:00+00:00' : null,
  canDelete: body !== null,
})

beforeEach(() => vi.clearAllMocks())

describe('mergeComments', () => {
  it('ordena del más viejo al más nuevo y reemplaza el que ya estaba', () => {
    const merged = mergeComments([message('02'), message('01')], [message('03'), message('02', null)])

    expect(merged.map((comment) => comment.id)).toEqual(['01', '02', '03'])
    expect(merged[1]?.body).toBeNull()
  })
})

describe('useConversation', () => {
  it('carga lo reciente y luego los anteriores', async () => {
    api.page.mockResolvedValueOnce({ items: [message('05'), message('04')], total: 5, before: '04' })
    api.page.mockResolvedValueOnce({ items: [message('03')], total: 5, before: null })
    const conversation = useConversation('I1')

    await conversation.refresh()
    await conversation.loadOlder()

    expect(api.page).toHaveBeenLastCalledWith('I1', '04')
    expect(conversation.comments.value.map((comment) => comment.id)).toEqual(['03', '04', '05'])
    expect(conversation.before.value).toBeNull()
  })

  it('un aviso nuevo se une sin perder los anteriores ya cargados', async () => {
    api.page.mockResolvedValueOnce({ items: [message('05')], total: 2, before: '05' })
    api.page.mockResolvedValueOnce({ items: [message('04')], total: 2, before: null })
    api.page.mockResolvedValueOnce({ items: [message('06'), message('05')], total: 3, before: '05' })
    const conversation = useConversation('I1')

    await conversation.refresh()
    await conversation.loadOlder()
    await conversation.refresh()

    expect(conversation.comments.value.map((comment) => comment.id)).toEqual(['04', '05', '06'])
    expect(conversation.total.value).toBe(3)
    expect(conversation.before.value).toBeNull()
  })

  it('escribe y borra actualizando el conteo; un error queda para mostrarlo', async () => {
    api.page.mockResolvedValueOnce({ items: [], total: 0, before: null })
    api.write.mockResolvedValueOnce(message('01'))
    api.remove.mockResolvedValueOnce(message('01', null))
    api.remove.mockRejectedValueOnce(new ApiError('http', 'COMMENT_DELETE_EXPIRED'))
    const conversation = useConversation('I1')
    await conversation.refresh()

    expect(await conversation.send('Hola')).toBe(true)
    expect(conversation.total.value).toBe(1)
    expect(await conversation.remove('01')).toBe(true)
    expect(conversation.comments.value[0]?.body).toBeNull()
    expect(conversation.total.value).toBe(0)
    expect(await conversation.remove('01')).toBe(false)
    expect(conversation.actionError.value?.code).toBe('COMMENT_DELETE_EXPIRED')
  })
})
