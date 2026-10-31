// Enlace al listado ya filtrado, para otros módulos (p. ej. cada cifra del dashboard lleva a los incidentes que cuenta).
// Usa la misma traducción a la URL que el listado. Documentado en docs/frontend/incidents.md
import type { RouteLocationRaw } from 'vue-router'
import { DEFAULT_CRITERIA } from '../domain/listing'
import { toQuery } from './listQuery'

export interface ListLinkFilter {
  status?: string[]
  priority?: string[]
  assignee?: string[]
  overdue?: boolean
  dueFrom?: string
  dueTo?: string
}

export function incidentListLocation(filter: ListLinkFilter): RouteLocationRaw {
  return {
    name: 'incidents',
    query: toQuery({
      ...DEFAULT_CRITERIA,
      statuses: filter.status ?? [],
      priorities: filter.priority ?? [],
      assignees: filter.assignee ?? [],
      overdue: filter.overdue ?? false,
      dueFrom: filter.dueFrom ?? null,
      dueTo: filter.dueTo ?? null,
    }),
  }
}
