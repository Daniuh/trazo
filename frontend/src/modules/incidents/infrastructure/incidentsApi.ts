// Repositorio de incidentes: todas las llamadas a /incidents y /catalogs pasan por aquí.
// Documentado en docs/frontend/incidents.md
import { http } from '@/shared/http'
import type { Activity, Catalogs, Incident, IncidentDraft, PersonRef } from '../domain/incident'
import {
  toActivity,
  toCatalogs,
  toIncident,
  toPayload,
  type ActivityDto,
  type CatalogsDto,
  type IncidentDto,
} from './mappers'

type Envelope<T> = { data: T }

export interface ActivityPage {
  items: Activity[]
  total: number
}

const path = (id: string) => `/incidents/${encodeURIComponent(id)}`

export const incidentsApi = {
  async catalogs(): Promise<Catalogs> {
    const { data } = await http.get<Envelope<CatalogsDto>>('/catalogs')
    return toCatalogs(data.data)
  },

  async assignees(): Promise<PersonRef[]> {
    const { data } = await http.get<Envelope<PersonRef[]>>('/incidents/assignees')
    return data.data
  },

  async get(id: string): Promise<Incident> {
    const { data } = await http.get<Envelope<IncidentDto>>(path(id))
    return toIncident(data.data)
  },

  // La clave identifica este intento de creación: si se reenvía, la API no crea otro
  async create(draft: IncidentDraft, idempotencyKey: string): Promise<Incident> {
    const { data } = await http.post<Envelope<IncidentDto>>('/incidents', toPayload(draft, true), {
      headers: { 'Idempotency-Key': idempotencyKey },
    })
    return toIncident(data.data)
  },

  async update(id: string, version: number, draft: IncidentDraft): Promise<Incident> {
    const { data } = await http.put<Envelope<IncidentDto>>(path(id), { ...toPayload(draft, false), version })
    return toIncident(data.data)
  },

  async changeStatus(id: string, version: number, status: string): Promise<Incident> {
    const { data } = await http.put<Envelope<IncidentDto>>(`${path(id)}/status`, { status, version })
    return toIncident(data.data)
  },

  async assign(id: string, version: number, assignee: string | null): Promise<Incident> {
    const { data } = await http.put<Envelope<IncidentDto>>(`${path(id)}/assignee`, { assignee, version })
    return toIncident(data.data)
  },

  async remove(id: string): Promise<void> {
    await http.delete(path(id))
  },

  async activities(id: string, page = 1): Promise<ActivityPage> {
    const { data } = await http.get<{ data: ActivityDto[]; meta: { total: number } }>(`${path(id)}/activities`, {
      params: { page },
    })
    return { items: data.data.map(toActivity), total: data.meta.total }
  },
}
