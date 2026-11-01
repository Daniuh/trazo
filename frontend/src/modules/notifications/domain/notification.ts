// Modelo del centro de notificaciones y cómo se cuenta cada aviso en palabras. Qué avisa a quién lo decide el back;
// aquí solo se arma el texto y a dónde lleva. Documentado en docs/frontend/notifications.md

export type NotificationCategory = 'conversations' | 'activity'

export type NotificationType =
  | 'comment_received'
  | 'incident_assigned'
  | 'status_changed'
  | 'incident_reopened'
  | 'reopen_rejected'
  // Avisos de coordinación para quienes reparten el trabajo
  | 'unassigned_created'
  | 'critical_created'
  | 'reopen_requested'
  | 'incident_overdue'

export interface Notification {
  id: string
  type: NotificationType
  category: NotificationCategory
  // Mensajes agrupados en un aviso de conversación
  occurrences: number
  actorName: string | null
  // Vacío si la persona ya no puede ver el incidente
  incident: { id: string; code: string; title: string } | null
  excerpt: string | null
  status: string | null
  commentDeleted: boolean
  // En los avisos de coordinación: el incidente no tiene responsable
  unassigned: boolean
  read: boolean
  createdAt: string
}

export type UnreadCounts = Record<NotificationCategory, number>

export const CATEGORIES: { value: NotificationCategory; label: string }[] = [
  { value: 'conversations', label: 'Conversaciones' },
  { value: 'activity', label: 'Actividad' },
]

export const noUnread = (): UnreadCounts => ({ conversations: 0, activity: 0 })

export const totalUnread = (counts: UnreadCounts): number => counts.conversations + counts.activity

// Qué pasó, en una frase. El código del incidente va aparte, en su propia línea
export function headline(item: Notification): string {
  const who = item.actorName ?? 'Alguien'
  switch (item.type) {
    case 'comment_received':
      return item.occurrences > 1 ? `${item.occurrences} mensajes nuevos, el último de ${who}` : `${who} escribió`
    case 'incident_assigned':
      return `${who} te asignó este incidente`
    case 'status_changed':
      return item.status ? `${who} lo pasó a ${item.status}` : `${who} cambió su estado`
    case 'incident_reopened':
      return `${who} lo reabrió`
    case 'reopen_rejected':
      return `${who} rechazó reabrirlo`
    case 'unassigned_created':
      return `${who} reportó un incidente sin asignar`
    case 'critical_created':
      return `${who} reportó un incidente crítico${item.unassigned ? ' sin asignar' : ''}`
    case 'reopen_requested':
      return `${who} pidió reabrirlo`
    case 'incident_overdue':
      return item.unassigned ? 'Venció sin resolverse y sigue sin responsable' : 'Venció sin resolverse'
  }
}

// Lo que se dijo: el mensaje, la nota del cambio o el motivo
export function detail(item: Notification): string | null {
  if (!item.incident) return null
  if (item.type === 'comment_received' && item.commentDeleted) return 'Mensaje eliminado'
  return item.excerpt
}

// A dónde lleva: el detalle del incidente, con su conversación abierta si el aviso es de un mensaje
export function target(
  item: Notification,
): { name: string; params: { id: string }; query: Record<string, string> } | null {
  if (!item.incident) return null
  return {
    name: 'incident-detail',
    params: { id: item.incident.id },
    query: item.category === 'conversations' ? { conversacion: '1' } : {},
  }
}

// Para el contador de la campana: "99+" a partir de cien
export const badgeText = (count: number): string => (count > 99 ? '99+' : String(count))
