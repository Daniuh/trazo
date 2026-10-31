// Estado del detalle de un incidente y sus acciones (estado, asignación, eliminar). Cada acción envía la versión
// conocida; si otra persona guardó antes, queda marcado el conflicto para ofrecer recargar. Documentado en docs/frontend/incidents.md
import { ref } from 'vue'
import { ApiError, toApiError } from '@/shared/http'
import type { Activity, Incident } from '../domain/incident'
import { incidentsApi } from '../infrastructure/incidentsApi'

const VERSION_CONFLICT = 'VERSION_CONFLICT'

export function useIncidentDetail(id: string) {
  const incident = ref<Incident | null>(null)
  const activities = ref<Activity[]>([])
  const loadError = ref<ApiError | null>(null)
  const actionError = ref<ApiError | null>(null)
  const conflict = ref(false)
  const loading = ref(true)
  const busy = ref(false)

  async function load(): Promise<void> {
    loading.value = true
    loadError.value = null
    try {
      const [detail, history] = await Promise.all([incidentsApi.get(id), incidentsApi.activities(id)])
      incident.value = detail
      activities.value = history.items
      conflict.value = false
      actionError.value = null
    } catch (raw) {
      loadError.value = toApiError(raw)
    } finally {
      loading.value = false
    }
  }

  // Ejecuta una acción, refresca el historial y deja el error listo para mostrarlo
  async function run(action: (current: Incident) => Promise<Incident>): Promise<boolean> {
    if (!incident.value || busy.value) return false
    busy.value = true
    actionError.value = null
    try {
      incident.value = await action(incident.value)
      activities.value = (await incidentsApi.activities(id)).items
      return true
    } catch (raw) {
      const error = toApiError(raw)
      conflict.value = error.code === VERSION_CONFLICT
      actionError.value = conflict.value ? null : error
      return false
    } finally {
      busy.value = false
    }
  }

  const changeStatus = (status: string) => run((current) => incidentsApi.changeStatus(id, current.version, status))
  const assign = (personId: string | null) => run((current) => incidentsApi.assign(id, current.version, personId))

  async function remove(): Promise<boolean> {
    busy.value = true
    actionError.value = null
    try {
      await incidentsApi.remove(id)
      return true
    } catch (raw) {
      actionError.value = toApiError(raw)
      return false
    } finally {
      busy.value = false
    }
  }

  return { incident, activities, loadError, actionError, conflict, loading, busy, load, changeStatus, assign, remove }
}
