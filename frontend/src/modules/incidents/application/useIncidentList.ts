// Estado del listado ligado a la URL: los criterios salen de la URL y cada cambio la actualiza, lo que dispara la consulta.
// Si llega una respuesta vieja después de una nueva, se descarta. Documentado en docs/frontend/incidents.md
import { computed, ref, shallowRef, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ApiError, toApiError } from '@/shared/http'
import { isSearchable, type IncidentSummary, type ListCriteria } from '../domain/listing'
import { listApi } from '../infrastructure/listApi'
import { fromQuery, toQuery } from './listQuery'

export function useIncidentList() {
  const route = useRoute()
  const router = useRouter()

  const criteria = computed(() => fromQuery(route.query))
  const items = shallowRef<IncidentSummary[]>([])
  const total = ref(0)
  const lastPage = ref(1)
  const loading = ref(false)
  const error = ref<ApiError | null>(null)
  let current: AbortController | null = null

  async function load(): Promise<void> {
    // Una búsqueda incompleta (menos de 3 letras) no se envía: la pantalla ya avisa
    if (!isSearchable(criteria.value.search)) return
    current?.abort()
    const controller = new AbortController()
    current = controller
    loading.value = true
    error.value = null
    try {
      const page = await listApi.list(criteria.value, controller.signal)
      items.value = page.items
      total.value = page.total
      lastPage.value = page.lastPage
    } catch (raw) {
      const failure = toApiError(raw)
      if (failure.kind !== 'canceled') error.value = failure
    } finally {
      if (current === controller) loading.value = false
    }
  }

  // Cambiar cualquier filtro vuelve a la primera página; cambiar solo la página no
  function update(changes: Partial<ListCriteria>): void {
    const next = { ...criteria.value, ...changes }
    if (!('page' in changes)) next.page = 1
    void router.replace({ query: toQuery(next) })
  }

  async function restore(id: string): Promise<ApiError | null> {
    try {
      await listApi.restore(id)
      await load()
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

  watch(() => route.fullPath, load, { immediate: true })

  return { criteria, items, total, lastPage, loading, error, load, update, restore, countFor }
}
