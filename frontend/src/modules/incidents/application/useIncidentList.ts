// Estado del listado de incidentes ligado a la URL (con useRouteList), más restaurar un eliminado, contar cuántos
// resultados daría un borrador de filtros y exportar lo filtrado. Documentado en docs/frontend/incidents.md
import { computed, ref } from 'vue'
import { saveFile } from '@/shared/download/saveFile'
import { toApiError, type ApiError } from '@/shared/http'
import { useRouteList } from '@/shared/listing/useRouteList'
import { isSearchable, type IncidentSummary, type ListCriteria, type ListPage } from '../domain/listing'
import { listApi } from '../infrastructure/listApi'
import { fromQuery, toQuery } from './listQuery'

export function useIncidentList() {
  const list = useRouteList<ListCriteria, IncidentSummary, ListPage>({
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

  // Descarga en CSV exactamente lo que se ve filtrado; el servidor decide si se puede y cuánto
  const exporting = ref(false)
  async function exportCsv(): Promise<ApiError | null> {
    exporting.value = true
    try {
      const { file, filename } = await listApi.export(list.criteria.value)
      saveFile(file, filename)
      return null
    } catch (raw) {
      return toApiError(raw)
    } finally {
      exporting.value = false
    }
  }

  // Total de cada grupo de la página actual (lista agrupada)
  const groups = computed(() => list.response.value?.groups ?? {})

  return { ...list, groups, restore, countFor, exporting, exportCsv }
}
