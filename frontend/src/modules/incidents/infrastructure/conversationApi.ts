// Repositorio de la conversación de un incidente: leer de a páginas, escribir y borrar un mensaje propio.
// Documentado en docs/frontend/incidents.md
import { http } from '@/shared/http'
import type { Comment } from '../domain/incident'
import { toComment, type CommentDto } from './mappers'

export interface CommentPage {
  // Del más nuevo al más viejo, como llegan de la API
  items: Comment[]
  total: number
  // Para pedir los anteriores; null si ya no hay más
  before: string | null
}

const path = (id: string) => `/incidents/${encodeURIComponent(id)}/comments`

export const conversationApi = {
  async page(id: string, before: string | null = null): Promise<CommentPage> {
    const { data } = await http.get<{ data: CommentDto[]; meta: { total: number; before: string | null } }>(path(id), {
      params: before ? { before } : {},
    })
    return { items: data.data.map(toComment), total: data.meta.total, before: data.meta.before }
  },

  async write(id: string, body: string): Promise<Comment> {
    const { data } = await http.post<{ data: CommentDto }>(path(id), { body })
    return toComment(data.data)
  },

  async remove(id: string, commentId: string): Promise<Comment> {
    const { data } = await http.delete<{ data: CommentDto }>(`${path(id)}/${encodeURIComponent(commentId)}`)
    return toComment(data.data)
  },
}
