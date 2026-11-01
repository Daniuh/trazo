// Personas a las que se puede asignar un incidente, según la API (activas y que atienden incidentes).
// Documentado en docs/frontend/incidents.md
import { ref } from 'vue'
import { ApiError, toApiError } from '@/shared/http'
import type { PersonRef } from '../domain/incident'
import { incidentsApi } from '../infrastructure/incidentsApi'

export function useAssignees() {
  const people = ref<PersonRef[]>([])
  const loading = ref(false)
  const error = ref<ApiError | null>(null)

  async function load(): Promise<void> {
    loading.value = true
    error.value = null
    try {
      people.value = await incidentsApi.assignees()
    } catch (raw) {
      error.value = toApiError(raw)
    } finally {
      loading.value = false
    }
  }

  return { people, loading, error, load }
}
