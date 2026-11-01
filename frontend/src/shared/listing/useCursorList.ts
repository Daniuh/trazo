// Listado por cursor ligado a la URL: los criterios salen de la URL y cada cambio vuelve a empezar; "cargar más" pide la
// siguiente tanda con el cursor que entregó la API y la agrega al final. El cursor no va en la URL: una recarga vuelve
// al principio. Si llega una respuesta vieja después de una nueva, se descarta. Documentado en docs/frontend/http-y-errores.md
import { computed, ref, shallowRef, watch, type ComputedRef } from 'vue'
import { useRoute, useRouter, type LocationQuery, type LocationQueryRaw } from 'vue-router'
import { ApiError, toApiError } from '@/shared/http'

export interface CursorPage<T, M> {
  items: T[]
  next: string | null
  meta: M
}

export interface CursorListOptions<C, T, M> {
  fromQuery: (query: LocationQuery) => C
  toQuery: (criteria: C) => LocationQueryRaw
  fetch: (criteria: C, cursor: string | null, signal: AbortSignal) => Promise<CursorPage<T, M>>
}

export function useCursorList<C, T, M>(options: CursorListOptions<C, T, M>) {
  const route = useRoute()
  const router = useRouter()

  const criteria: ComputedRef<C> = computed(() => options.fromQuery(route.query))
  const items = shallowRef<T[]>([])
  const meta = shallowRef<M | null>(null)
  const next = ref<string | null>(null)
  const loading = ref(false)
  const loadingMore = ref(false)
  const error = ref<ApiError | null>(null)
  let current: AbortController | null = null

  async function request(cursor: string | null): Promise<void> {
    current?.abort()
    const controller = new AbortController()
    current = controller
    const busy = cursor === null ? loading : loadingMore
    busy.value = true
    error.value = null
    try {
      const page = await options.fetch(criteria.value, cursor, controller.signal)
      items.value = cursor === null ? page.items : [...items.value, ...page.items]
      next.value = page.next
      meta.value = page.meta
    } catch (raw) {
      const failure = toApiError(raw)
      if (failure.kind !== 'canceled') error.value = failure
    } finally {
      if (current === controller) busy.value = false
    }
  }

  const load = () => request(null)

  function loadMore(): Promise<void> {
    return next.value ? request(next.value) : Promise.resolve()
  }

  function update(changes: Partial<C>): void {
    void router.replace({ query: options.toQuery({ ...criteria.value, ...changes }) })
  }

  watch(() => route.fullPath, load, { immediate: true })

  return { criteria, items, meta, next, loading, loadingMore, error, load, loadMore, update }
}
