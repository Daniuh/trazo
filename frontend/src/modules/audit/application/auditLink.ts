// Enlace a la auditoría ya filtrada, para llegar desde otras pantallas (p. ej. "Ver su actividad" en la ficha de una
// persona). Documentado en docs/frontend/audit.md
import type { RouteLocationRaw } from 'vue-router'
import { EMPTY_CRITERIA, type AuditCriteria, type AuditTab } from '../domain/audit'
import { toAuditQuery } from './auditQuery'

export const AUDIT_ROUTES: Record<AuditTab, string> = { incidents: 'audit-incidents', admin: 'audit-admin' }

export function auditLocation(tab: AuditTab, filters: Partial<AuditCriteria> = {}): RouteLocationRaw {
  return { name: AUDIT_ROUTES[tab], query: toAuditQuery({ ...EMPTY_CRITERIA, ...filters }, tab) }
}
