// Traduce los criterios de la auditoría a la URL y de vuelta. El filtro de "sobre qué" se llama "incident" en la pestaña
// de incidentes y "user" en la de administración; el rol solo existe en administración. Documentado en docs/frontend/audit.md
import type { LocationQuery, LocationQueryRaw } from 'vue-router'
import { compactQuery, queryList, queryOne } from '@/shared/routing/queryValues'
import type { AuditCriteria, AuditTab } from '../domain/audit'

const targetKey = (tab: AuditTab) => (tab === 'incidents' ? 'incident' : 'user')

export function fromAuditQuery(query: LocationQuery, tab: AuditTab): AuditCriteria {
  return {
    from: queryOne(query.from),
    to: queryOne(query.to),
    actor: queryOne(query.actor),
    types: queryList(query.type),
    target: queryOne(query[targetKey(tab)]),
    role: tab === 'admin' ? queryOne(query.role) : null,
  }
}

export function toAuditQuery(criteria: AuditCriteria, tab: AuditTab): LocationQueryRaw {
  return compactQuery({
    from: criteria.from,
    to: criteria.to,
    actor: criteria.actor,
    type: criteria.types.join(','),
    [targetKey(tab)]: criteria.target?.trim(),
    role: tab === 'admin' ? criteria.role : null,
  })
}
