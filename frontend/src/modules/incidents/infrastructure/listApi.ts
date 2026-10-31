// Repositorio del listado: arma los parámetros de GET /incidents y traduce la página que responde.
// Documentado en docs/frontend/incidents.md
import { http } from '@/shared/http'
import type { ListCriteria, ListPage } from '../domain/listing'
import { toIncident, type IncidentDto } from './mappers'

type SummaryDto = Omit<IncidentDto, 'description' | 'next_statuses'>

interface ListResponse {
  data: SummaryDto[]
  meta: { page: number; per_page: number; total: number; last_page: number }
}

export function toParams(criteria: ListCriteria): Record<string, string | number> {
  const params: Record<string, string | number> = {
    page: criteria.page,
    per_page: criteria.perPage,
    sort: `${criteria.descending ? '-' : ''}${criteria.sort}`,
  }
  const add = (key: string, value: string | null) => {
    if (value) params[key] = value
  }

  add('search', criteria.search.trim() || null)
  add('filter[status]', criteria.statuses.join(','))
  add('filter[priority]', criteria.priorities.join(','))
  add('filter[assignee]', criteria.assignees.join(','))
  add('filter[creator]', criteria.creator)
  add('filter[created_from]', criteria.createdFrom)
  add('filter[created_to]', criteria.createdTo)
  add('filter[due_from]', criteria.dueFrom)
  add('filter[due_to]', criteria.dueTo)
  if (criteria.overdue) params['filter[overdue]'] = 1
  if (criteria.deleted) params['filter[deleted]'] = 1
  return params
}

export const listApi = {
  async list(criteria: ListCriteria, signal?: AbortSignal): Promise<ListPage> {
    const { data } = await http.get<ListResponse>('/incidents', { params: toParams(criteria), signal })
    return {
      // El resumen no trae descripción ni estados siguientes; se completan vacíos para reutilizar el mapeo
      items: data.data.map((dto) => toIncident({ ...dto, description: '', next_statuses: [] })),
      total: data.meta.total,
      lastPage: data.meta.last_page,
    }
  },

  async restore(id: string): Promise<void> {
    await http.post(`/incidents/${encodeURIComponent(id)}/restore`)
  },
}
