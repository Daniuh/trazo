// Tipos de la pantalla de sistema: salud por componente, eventos agrupados, jobs fallidos y criterios de la lista.
// Qué está bien o mal lo decide la API; el front solo lo muestra. Documentado en docs/frontend/system.md

export type HealthStatus = 'ok' | 'warning' | 'failing'

export type ComponentCode = 'database' | 'redis' | 'queue' | 'worker' | 'scheduler' | 'realtime' | 'storage'

export interface HealthComponent {
  code: ComponentCode
  status: HealthStatus
  latencyMs?: number | null
  pending?: number
  oldestSeconds?: number | null
  failed?: number
  secondsAgo?: number | null
  freeBytes?: number
  totalBytes?: number
}

export interface Health {
  checkedAt: string
  status: HealthStatus
  components: HealthComponent[]
  maintenance: { since: string; enabledBy: string } | null
  app: { environment: string; laravel: string; php: string }
}

export type SystemAction = 'restart-workers' | 'restart-realtime' | 'clear-cache'

export type Severity = 'info' | 'warning' | 'critical'

export type EventStatus = 'open' | 'acknowledged' | 'resolved'

export interface EventType {
  code: string
  name: string
  category: string
  severity: Severity
}

export interface SystemEvent {
  id: number
  type: EventType
  message: string
  context: Record<string, unknown> | null
  occurrences: number
  firstSeenAt: string
  lastSeenAt: string
  traceId: string | null
  status: EventStatus
  statusChangedBy: string | null
  statusChangedAt: string | null
}

export interface FailedJob {
  id: string
  job: string
  queue: string
  failedAt: string
  error: string
}

// Qué eventos se ven: pendientes (abiertos y revisados), resueltos o todos
export type EventView = 'pending' | 'resolved' | 'all'

export interface EventCriteria {
  view: EventView
  severity: Severity | null
  type: string | null
}

export const DEFAULT_EVENT_CRITERIA: EventCriteria = { view: 'pending', severity: null, type: null }

export const EVENTS_PER_PAGE = 25

export const VIEW_STATUSES: Record<EventView, EventStatus[]> = {
  pending: ['open', 'acknowledged'],
  resolved: ['resolved'],
  all: [],
}

// Cambios de estado que ofrece cada uno: abierto → revisado o resuelto; revisado → resuelto; resuelto → reabrir
export function nextStatuses(status: EventStatus): EventStatus[] {
  if (status === 'open') return ['acknowledged', 'resolved']
  if (status === 'acknowledged') return ['resolved']
  return ['open']
}

export function activeEventFilterCount(criteria: EventCriteria): number {
  return [criteria.view !== 'pending', criteria.severity, criteria.type].filter(Boolean).length
}
