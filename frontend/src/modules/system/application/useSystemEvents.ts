// Estado de la pestaña de eventos: la lista por cursor ligada a la URL, el catálogo de tipos para el filtro y el
// cambio de estado de un evento (reemplaza la fila sin volver a pedir la lista). Documentado en docs/frontend/system.md
import { onMounted, shallowRef } from 'vue'
import { toApiError, type ApiError } from '@/shared/http'
import { useCursorList } from '@/shared/listing/useCursorList'
import type { EventCriteria, EventStatus, EventType, SystemEvent } from '../domain/system'
import { systemApi } from '../infrastructure/systemApi'
import { fromEventQuery, toEventQuery } from './systemQuery'

export function useSystemEvents() {
  const list = useCursorList<EventCriteria, SystemEvent, null>({
    fromQuery: fromEventQuery,
    toQuery: toEventQuery,
    fetch: (criteria, cursor, signal) => systemApi.events(criteria, cursor, signal),
  })
  const types = shallowRef<EventType[]>([])

  onMounted(async () => {
    try {
      types.value = await systemApi.eventTypes()
    } catch {
      // Sin catálogo, el filtro por tipo queda vacío; la lista funciona igual
    }
  })

  async function changeStatus(id: number, status: EventStatus): Promise<ApiError | null> {
    try {
      const updated = await systemApi.changeStatus(id, status)
      list.items.value = list.items.value.map((event) => (event.id === id ? updated : event))
      return null
    } catch (raw) {
      return toApiError(raw)
    }
  }

  return { ...list, types, changeStatus }
}
