// Actividad del incidente (ADR-0083): historial y mensajes en una sola línea, del más nuevo al más viejo, con el
// filtro "Todo · Mensajes · Cambios". Documentado en docs/frontend/incidents.md
import type { Activity, Comment } from './incident'

export type FeedFilter = 'all' | 'messages' | 'changes'

// El historial del detalle muestra solo lo más reciente; el resto está en el panel de actividad
export const RECENT_ACTIVITY = 5

// Pestañas del panel del detalle: el chat (donde se escribe) y toda la actividad
export type ActivityPanelTab = 'chat' | 'activity'

export type FeedEntry =
  | { kind: 'message'; key: string; at: string; comment: Comment }
  | { kind: 'change'; key: string; at: string; activity: Activity }

export function activityFeed(activities: Activity[], comments: Comment[], filter: FeedFilter = 'all'): FeedEntry[] {
  const changes: FeedEntry[] =
    filter === 'messages'
      ? []
      : activities.map((activity, index) => ({
          kind: 'change',
          key: `change-${activity.createdAt}-${index}`,
          at: activity.createdAt,
          activity,
        }))
  const messages: FeedEntry[] =
    filter === 'changes'
      ? []
      : comments.map((comment) => ({ kind: 'message', key: comment.id, at: comment.createdAt, comment }))

  // Del más nuevo al más viejo; a la misma hora, el mensaje después del cambio que lo acompaña
  return [...changes, ...messages].sort(
    (a, b) => b.at.localeCompare(a.at) || (a.kind === b.kind ? 0 : a.kind === 'message' ? -1 : 1),
  )
}
