// "¿Ya lo reportaron?": busca incidentes parecidos al título mientras se escribe, con la misma búsqueda y visibilidad
// del listado. Espera a que la persona deje de escribir y descarta respuestas viejas. Si falla, no muestra nada: es
// una ayuda, no algo que impida reportar. Documentado en docs/frontend/incidents.md
import { onBeforeUnmount, ref, shallowRef, watch, type Ref } from 'vue'
import { DEFAULT_CRITERIA, type IncidentSummary } from '../domain/listing'
import { similarQueries } from '../domain/reporting'
import { listApi } from '../infrastructure/listApi'

export const SIMILAR_LIMIT = 3
const DELAY_MS = 400

export function useSimilarIncidents(title: Ref<string>, enabled: boolean) {
  const items = shallowRef<IncidentSummary[]>([])
  const total = ref(0)
  let timer: ReturnType<typeof setTimeout> | undefined
  let current: AbortController | null = null

  // De la búsqueda más precisa a la más amplia, hasta que alguna encuentre algo
  async function search(queries: string[]): Promise<void> {
    current?.abort()
    const controller = new AbortController()
    current = controller
    try {
      for (const query of queries) {
        // Como tabla: sin grupos, los más recientes primero
        const page = await listApi.list(
          { ...DEFAULT_CRITERIA, view: 'table', search: query, perPage: SIMILAR_LIMIT },
          controller.signal,
        )
        if (current !== controller) return
        items.value = page.items
        total.value = page.total
        if (page.total > 0) return
      }
    } catch {
      if (current === controller) items.value = []
    }
  }

  watch(
    title,
    (value) => {
      clearTimeout(timer)
      const queries = enabled ? similarQueries(value) : []
      if (queries.length === 0) {
        current?.abort()
        items.value = []
        total.value = 0
        return
      }
      timer = setTimeout(() => void search(queries), DELAY_MS)
    },
    { immediate: true },
  )

  onBeforeUnmount(() => {
    clearTimeout(timer)
    current?.abort()
  })

  return { items, total }
}
