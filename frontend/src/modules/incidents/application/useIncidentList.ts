// Estado del listado de incidentes ligado a la URL (con useRouteList), más restaurar un eliminado y contar cuántos
// resultados daría un borrador de filtros. Documentado en docs/frontend/incidents.md
import { toApiError, type ApiError } from '@/shared/http'
import { useRouteList } from '@/shared/listing/useRouteList'
import { isSearchable, type IncidentSummary, type ListCriteria } from '../domain/listing'
import { listApi } from '../infrastructure/listApi'
import { fromQuery, toQuery } from './listQuery'

export function useIncidentList() {
  const list = useRouteList<ListCriteria, IncidentSummary>({
    fromQuery,
    toQuery,
    fetch: (criteria, signal) => listApi.list(criteria, signal),
    // Una búsqueda incompleta (menos de 3 letras) no se envía: la pantalla ya avisa
    ready: (criteria) => isSearchable(criteria.search),
  })

  async function restore(id: string): Promise<ApiError | null> {
    try {
      await listApi.restore(id)
      await list.load()
      return null
    } catch (raw) {
      return toApiError(raw)
    }
  }

  // Cuántos resultados daría un borrador de filtros, sin cambiar el listado (panel de filtros en celular)
  async function countFor(draft: ListCriteria): Promise<number | null> {
    try {
      return (await listApi.list({ ...draft, page: 1, perPage: 1 })).total
    } catch {
      return null
    }
  }

  return { ...list, restore, countFor }
}
