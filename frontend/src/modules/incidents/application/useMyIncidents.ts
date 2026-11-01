// Estado de "Mis incidentes": el tablero completo, cargado de una vez, y mover una tarjeta a otro estado (con la misma
// API del detalle). Si otra persona guardó antes, se avisa el conflicto y se vuelve a leer. Las recargas por tiempo real
// no muestran el esqueleto ni borran lo que hay. Documentado en docs/frontend/incidents.md
import { ref, shallowRef } from 'vue'
import { toApiError, type ApiError } from '@/shared/http'
import type { Incident } from '../domain/incident'
import { boardApi, type Board } from '../infrastructure/boardApi'
import { incidentsApi } from '../infrastructure/incidentsApi'

const VERSION_CONFLICT = 'VERSION_CONFLICT'

export function useMyIncidents() {
  const board = shallowRef<Board | null>(null)
  const loading = ref(false)
  const loadError = ref<ApiError | null>(null)
  const actionError = ref<ApiError | null>(null)
  const conflict = ref(false)
  const moving = ref<string | null>(null)

  async function load(): Promise<void> {
    loading.value = true
    try {
      board.value = await boardApi.mine()
      loadError.value = null
    } catch (raw) {
      // Si ya había un tablero, se queda a la vista; el error solo reemplaza la pantalla la primera vez
      loadError.value = toApiError(raw)
    } finally {
      loading.value = false
    }
  }

  // Lleva la tarjeta a otro estado. True si salió bien; si no, deja el error listo para el diálogo de nota
  async function move(item: Incident, status: string, note: string | null): Promise<boolean> {
    if (moving.value) return false
    moving.value = item.id
    actionError.value = null
    conflict.value = false
    try {
      const updated = await incidentsApi.changeStatus(item.id, item.version, status, note)
      if (board.value) {
        board.value = {
          ...board.value,
          items: board.value.items.map((other) => (other.id === item.id ? updated : other)),
        }
      }
      return true
    } catch (raw) {
      const error = toApiError(raw)
      conflict.value = error.code === VERSION_CONFLICT
      actionError.value = conflict.value ? null : error
      return false
    } finally {
      moving.value = null
    }
  }

  function clearError(): void {
    actionError.value = null
    conflict.value = false
  }

  return { board, loading, loadError, actionError, conflict, moving, load, move, clearError }
}
