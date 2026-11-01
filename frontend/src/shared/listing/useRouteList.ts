// Listado ligado a la URL: los criterios salen de la URL y cada cambio la actualiza, lo que dispara la consulta.
// Si llega una respuesta vieja después de una nueva, se descarta. Lo usan los listados de los módulos.
// Documentado en docs/frontend/http-y-errores.md
import { computed, ref, shallowRef, watch, type ComputedRef } from 'vue'
import { useRoute, useRouter, type LocationQuery, type LocationQueryRaw } from 'vue-router'
import { ApiError, toApiError } from '@/shared/http'

export interface RoutePage<T> {
  items: T[]
  total: number
  lastPage: number
}

export interface RouteListOptions<C extends { page: number }, T, P extends RoutePage<T> = RoutePage<T>> {
  fromQuery: (query: LocationQuery) => C
  toQuery: (criteria: C) => LocationQueryRaw
  // Puede traer más que la página (p. ej. totales por grupo): queda entero en `response`
  fetch: (criteria: C, signal: AbortSignal) => Promise<P>
  // Criterios que todavía no se deben consultar (p. ej. una búsqueda incompleta)
  ready?: (criteria: C) => boolean
}

export function useRouteList<C extends { page: number }, T, P extends RoutePage<T> = RoutePage<T>>(
  options: RouteListOptions<C, T, P>,
) {
  const route = useRoute()
  const router = useRouter()

  const criteria: ComputedRef<C> = computed(() => options.fromQuery(route.query))
  const items = shallowRef<T[]>([])
  const response = shallowRef<P | null>(null)
  const total = ref(0)
  const lastPage = ref(1)
  const loading = ref(false)
  const error = ref<ApiError | null>(null)
  let current: AbortController | null = null

  async function load(): Promise<void> {
    if (options.ready && !options.ready(criteria.value)) return
    current?.abort()
    const controller = new AbortController()
    current = controller
    loading.value = true
    error.value = null
    try {
      const page = await options.fetch(criteria.value, controller.signal)
      response.value = page
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
  function update(changes: Partial<C>): void {
    const next = { ...criteria.value, ...changes }
    if (!('page' in changes)) next.page = 1
    void router.replace({ query: options.toQuery(next) })
  }

  watch(() => route.fullPath, load, { immediate: true })

  return { criteria, items, response, total, lastPage, loading, error, load, update }
}
