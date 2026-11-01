// Estado de la pantalla de auditoría: la lista de la pestaña (por cursor, ligada a la URL) y su catálogo.
// Documentado en docs/frontend/audit.md
import { onMounted, ref, shallowRef } from 'vue'
import { ApiError, toApiError } from '@/shared/http'
import { useCursorList } from '@/shared/listing/useCursorList'
import type { AuditCatalog, AuditCriteria, AuditEntry, AuditPeriod, AuditTab } from '../domain/audit'
import { auditApi } from '../infrastructure/auditApi'
import { fromAuditQuery, toAuditQuery } from './auditQuery'

export function useAuditList(tab: AuditTab) {
  return useCursorList<AuditCriteria, AuditEntry, AuditPeriod>({
    fromQuery: (query) => fromAuditQuery(query, tab),
    toQuery: (criteria) => toAuditQuery(criteria, tab),
    fetch: (criteria, cursor, signal) =>
      tab === 'incidents' ? auditApi.incidents(criteria, cursor, signal) : auditApi.admin(criteria, cursor, signal),
  })
}

export function useAuditCatalog() {
  const catalog = shallowRef<AuditCatalog | null>(null)
  const error = ref<ApiError | null>(null)

  async function load(): Promise<void> {
    error.value = null
    try {
      catalog.value = await auditApi.catalog()
    } catch (raw) {
      error.value = toApiError(raw)
    }
  }

  onMounted(load)

  return { catalog, error, load }
}
