// Criterios de la lista de eventos en la URL y de vuelta: vista (pendientes, resueltos o todos), severidad y tipo.
// Las rutas de la pantalla, por pestaña. Documentado en docs/frontend/system.md
import type { LocationQuery, LocationQueryRaw } from 'vue-router'
import { compactQuery, queryChoice, queryOne } from '@/shared/routing/queryValues'
import { DEFAULT_EVENT_CRITERIA, type EventCriteria, type EventView, type Severity } from '../domain/system'

export const SYSTEM_ROUTES = { health: 'system-health', events: 'system-events' } as const

const VIEWS: readonly EventView[] = ['pending', 'resolved', 'all']
const SEVERITIES: readonly Severity[] = ['critical', 'warning', 'info']

export function fromEventQuery(query: LocationQuery): EventCriteria {
  const severity = queryOne(query.severity)
  return {
    view: queryChoice(query.view, VIEWS, DEFAULT_EVENT_CRITERIA.view),
    severity: severity !== null && (SEVERITIES as readonly string[]).includes(severity) ? (severity as Severity) : null,
    type: queryOne(query.type),
  }
}

export function toEventQuery(criteria: EventCriteria): LocationQueryRaw {
  return compactQuery({
    // La vista por defecto no se escribe: la URL queda limpia
    view: criteria.view === DEFAULT_EVENT_CRITERIA.view ? null : criteria.view,
    severity: criteria.severity,
    type: criteria.type,
  })
}
