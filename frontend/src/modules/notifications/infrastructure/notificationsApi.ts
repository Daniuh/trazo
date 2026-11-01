// Repositorio del centro de notificaciones: leer las propias por pestaña, contar las sin leer y marcarlas como leídas.
// Documentado en docs/frontend/notifications.md
import { http } from '@/shared/http'
import type { Notification, NotificationCategory, NotificationType, UnreadCounts } from '../domain/notification'

interface NotificationDto {
  id: string
  type: NotificationType
  category: NotificationCategory
  occurrences: number
  actor: { name: string } | null
  incident: { id: string; code: string; title: string } | null
  excerpt: string | null
  status: string | null
  comment_deleted: boolean
  unassigned: boolean
  read: boolean
  created_at: string
}

export interface NotificationPage {
  items: Notification[]
  unread: UnreadCounts
  // Para pedir las anteriores; null si ya no hay más
  before: string | null
}

export function toNotification(dto: NotificationDto): Notification {
  return {
    id: dto.id,
    type: dto.type,
    category: dto.category,
    occurrences: dto.occurrences,
    actorName: dto.actor?.name ?? null,
    incident: dto.incident,
    excerpt: dto.excerpt,
    status: dto.status,
    commentDeleted: dto.comment_deleted,
    unassigned: dto.unassigned,
    read: dto.read,
    createdAt: dto.created_at,
  }
}

export const notificationsApi = {
  async page(category: NotificationCategory, before: string | null = null): Promise<NotificationPage> {
    const { data } = await http.get<{ data: NotificationDto[]; meta: { unread: UnreadCounts; before: string | null } }>(
      '/notifications',
      { params: before ? { category, before } : { category } },
    )
    return { items: data.data.map(toNotification), unread: data.meta.unread, before: data.meta.before }
  },

  async unread(): Promise<UnreadCounts> {
    const { data } = await http.get<{ data: UnreadCounts }>('/notifications/unread')
    return data.data
  },

  async markRead(id: string): Promise<UnreadCounts> {
    const { data } = await http.post<{ data: UnreadCounts }>(`/notifications/${encodeURIComponent(id)}/read`)
    return data.data
  },

  // Todas las de una pestaña; con incidentId, solo las de ese incidente
  async markAllRead(category: NotificationCategory, incidentId: string | null = null): Promise<UnreadCounts> {
    const { data } = await http.post<{ data: UnreadCounts }>(
      '/notifications/read',
      incidentId ? { category, incident: incidentId } : { category },
    )
    return data.data
  },
}
