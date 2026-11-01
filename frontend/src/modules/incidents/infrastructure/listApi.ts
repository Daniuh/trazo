// Repositorio del listado: arma los parámetros de GET /incidents, traduce la página que responde y descarga la
// exportación en CSV con los mismos criterios. Documentado en docs/frontend/incidents.md
import { filenameFrom } from '@/shared/download/saveFile'
import { http } from '@/shared/http'
import { effectiveGroup, type ListCriteria, type ListPage } from '../domain/listing'
import { toIncident, type IncidentDto } from './mappers'

const EXPORT_TIMEOUT_MS = 120_000

type SummaryDto = Omit<IncidentDto, 'description' | 'next_statuses'>

interface ListResponse {
  data: SummaryDto[]
  meta: { page: number; per_page: number; total: number; last_page: number; groups?: { key: string; total: number }[] }
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

  const group = effectiveGroup(criteria)
  add('group_by', group === 'none' ? null : group)
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
  if (criteria.reopenRequested) params['filter[reopen_requested]'] = 1
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
      groups: Object.fromEntries((data.meta.groups ?? []).map((group) => [group.key, group.total])),
    }
  },

  // Mismos filtros y orden del listado, sin página. Puede tardar más que una consulta normal
  async export(criteria: ListCriteria): Promise<{ file: Blob; filename: string }> {
    const params = toParams(criteria)
    delete params.page
    delete params.per_page
    const response = await http.get<Blob>('/incidents/export', {
      params,
      responseType: 'blob',
      timeout: EXPORT_TIMEOUT_MS,
    })
    const disposition = response.headers['content-disposition'] as string | undefined
    return { file: response.data, filename: filenameFrom(disposition, 'incidentes.csv') }
  },

  async restore(id: string): Promise<void> {
    await http.post(`/incidents/${encodeURIComponent(id)}/restore`)
  },
}
