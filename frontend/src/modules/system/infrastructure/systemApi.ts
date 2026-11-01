// Repositorio de la pantalla de sistema: salud, eventos (por cursor), tipos, jobs fallidos y acciones. Las acciones
// llevan el código de reautenticación cuando la persona ya lo escribió. Documentado en docs/frontend/system.md
import { http } from '@/shared/http'
import type { CursorPage } from '@/shared/listing/useCursorList'
import {
  EVENTS_PER_PAGE,
  VIEW_STATUSES,
  type EventCriteria,
  type EventStatus,
  type EventType,
  type FailedJob,
  type Health,
  type HealthComponent,
  type SystemAction,
  type SystemEvent,
} from '../domain/system'

type Envelope<T> = { data: T }

interface HealthDto {
  checked_at: string
  status: Health['status']
  components: (Pick<HealthComponent, 'code' | 'status' | 'pending' | 'failed'> & {
    latency_ms?: number | null
    oldest_seconds?: number | null
    seconds_ago?: number | null
    free_bytes?: number
    total_bytes?: number
  })[]
  maintenance: { since: string; enabled_by: string } | null
  app: Health['app']
}

interface EventDto {
  id: number
  type: EventType
  message: string
  context: Record<string, unknown> | null
  occurrences: number
  first_seen_at: string
  last_seen_at: string
  trace_id: string | null
  status: EventStatus
  status_changed_by: string | null
  status_changed_at: string | null
}

interface FailedJobDto {
  id: string
  job: string
  queue: string
  failed_at: string
  error: string
}

const withCode = (code?: string) => (code ? { mfa_code: code } : {})

function toEvent(dto: EventDto): SystemEvent {
  return {
    id: dto.id,
    type: dto.type,
    message: dto.message,
    context: dto.context,
    occurrences: dto.occurrences,
    firstSeenAt: dto.first_seen_at,
    lastSeenAt: dto.last_seen_at,
    traceId: dto.trace_id,
    status: dto.status,
    statusChangedBy: dto.status_changed_by,
    statusChangedAt: dto.status_changed_at,
  }
}

export function eventParams(criteria: EventCriteria, cursor: string | null): Record<string, string | number> {
  const params: Record<string, string | number> = { per_page: EVENTS_PER_PAGE }
  const statuses = VIEW_STATUSES[criteria.view]
  if (statuses.length > 0) params['filter[status]'] = statuses.join(',')
  if (criteria.severity) params['filter[severity]'] = criteria.severity
  if (criteria.type) params['filter[type]'] = criteria.type
  if (cursor) params.cursor = cursor
  return params
}

export const systemApi = {
  async health(): Promise<Health> {
    const { data } = await http.get<Envelope<HealthDto>>('/system/health')
    const dto = data.data
    return {
      checkedAt: dto.checked_at,
      status: dto.status,
      components: dto.components.map((c) => ({
        code: c.code,
        status: c.status,
        latencyMs: c.latency_ms,
        pending: c.pending,
        oldestSeconds: c.oldest_seconds,
        failed: c.failed,
        secondsAgo: c.seconds_ago,
        freeBytes: c.free_bytes,
        totalBytes: c.total_bytes,
      })),
      maintenance: dto.maintenance ? { since: dto.maintenance.since, enabledBy: dto.maintenance.enabled_by } : null,
      app: dto.app,
    }
  },

  async events(
    criteria: EventCriteria,
    cursor: string | null,
    signal?: AbortSignal,
  ): Promise<CursorPage<SystemEvent, null>> {
    const { data } = await http.get<{ data: EventDto[]; meta: { next_cursor: string | null } }>('/system/events', {
      params: eventParams(criteria, cursor),
      signal,
    })
    return { items: data.data.map(toEvent), next: data.meta.next_cursor, meta: null }
  },

  async eventTypes(): Promise<EventType[]> {
    const { data } = await http.get<Envelope<EventType[]>>('/system/events/types')
    return data.data
  },

  async changeStatus(id: number, status: EventStatus): Promise<SystemEvent> {
    const { data } = await http.patch<Envelope<EventDto>>(`/system/events/${id}`, { status })
    return toEvent(data.data)
  },

  async failedJobs(): Promise<{ items: FailedJob[]; total: number }> {
    const { data } = await http.get<{ data: FailedJobDto[]; meta: { total: number } }>('/system/failed-jobs')
    return {
      items: data.data.map((dto) => ({ ...dto, failedAt: dto.failed_at })),
      total: data.meta.total,
    }
  },

  async retryJobs(ids: string[] | null, code?: string): Promise<number> {
    const body = ids === null ? { all: true } : { ids }
    const { data } = await http.post<Envelope<{ retried: number }>>('/system/failed-jobs/retry', {
      ...body,
      ...withCode(code),
    })
    return data.data.retried
  },

  async discardJob(id: string, code?: string): Promise<void> {
    await http.delete(`/system/failed-jobs/${encodeURIComponent(id)}`, { data: withCode(code) })
  },

  async run(action: SystemAction, code?: string): Promise<void> {
    await http.post(`/system/actions/${action}`, withCode(code))
  },

  async setMaintenance(enabled: boolean, code?: string): Promise<void> {
    await http.put('/system/maintenance', { enabled, ...withCode(code) })
  },
}
