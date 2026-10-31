// Formulario único para crear y editar. Carga lo que necesita, valida solo la forma (la API decide), sugiere el
// vencimiento según la prioridad y protege la creación con Idempotency-Key. Documentado en docs/frontend/incidents.md
import { computed, reactive, ref, shallowRef } from 'vue'
import { fieldMessageFor, fieldMessagesFor, messageFor } from '@/shared/errors'
import { ApiError, toApiError } from '@/shared/http'
import { DESCRIPTION_MAX, TITLE_MAX, type Incident, type IncidentDraft } from '../domain/incident'
import { incidentsApi } from '../infrastructure/incidentsApi'
import { useCatalogStore } from './useCatalogStore'

const DEFAULT_PRIORITY = 'medium'

export type FormStatus = 'loading' | 'ready' | 'not-editable' | 'error'

export function useIncidentForm(id: string | null) {
  const catalogs = useCatalogStore()
  const existing = shallowRef<Incident | null>(null)

  // dueDate: ISO en UTC; null deja que la API lo calcule con la prioridad
  const form = reactive({
    title: '',
    description: '',
    priority: DEFAULT_PRIORITY,
    dueDate: null as string | null,
    assignee: null as string | null,
  })
  const status = ref<FormStatus>('loading')
  const loadError = ref<ApiError | null>(null)
  const fieldErrors = ref<Record<string, string>>({})
  const formError = ref<string | null>(null)
  const conflict = ref(false)
  const submitting = ref(false)

  // Misma clave mientras se reintenta el mismo envío; una nueva si los datos cambian
  let attempt: { payload: string; key: string } | null = null

  async function prepare(): Promise<FormStatus> {
    status.value = 'loading'
    loadError.value = null
    conflict.value = false
    try {
      const [incident] = await Promise.all([id ? incidentsApi.get(id) : null, catalogs.load()])
      existing.value = incident
      if (incident) {
        Object.assign(form, {
          title: incident.title,
          description: incident.description,
          priority: incident.priority.code,
          dueDate: incident.dueDate,
        })
      }
      status.value = incident && !incident.actions.update ? 'not-editable' : 'ready'
    } catch (raw) {
      loadError.value = toApiError(raw)
      status.value = 'error'
    }
    return status.value
  }

  // Solo informativa: si el campo queda vacío, la API calcula el vencimiento con la prioridad
  const suggestedDueDate = computed(() => {
    const minutes = catalogs.priority(form.priority)?.resolutionMinutes
    return minutes ? new Date(Date.now() + minutes * 60_000).toISOString() : null
  })

  function validate(): boolean {
    const errors: Record<string, string> = {}
    if (!form.title.trim()) errors.title = fieldMessageFor({ code: 'REQUIRED' })
    else if (form.title.length > TITLE_MAX)
      errors.title = fieldMessageFor({ code: 'TOO_LONG', params: { max: TITLE_MAX } })
    if (!form.description.trim()) errors.description = fieldMessageFor({ code: 'REQUIRED' })
    else if (form.description.length > DESCRIPTION_MAX) {
      errors.description = fieldMessageFor({ code: 'TOO_LONG', params: { max: DESCRIPTION_MAX } })
    }
    fieldErrors.value = errors
    return Object.keys(errors).length === 0
  }

  function draft(): IncidentDraft {
    // Al editar, un vencimiento sin tocar no se envía: así un incidente vencido se puede seguir editando
    const dueChanged = form.dueDate !== null && form.dueDate !== existing.value?.dueDate
    return {
      title: form.title.trim(),
      description: form.description.trim(),
      priority: form.priority,
      dueDate: dueChanged ? form.dueDate : null,
      assignee: form.assignee,
    }
  }

  function keyFor(payload: IncidentDraft): string {
    const serialized = JSON.stringify(payload)
    if (attempt?.payload !== serialized) attempt = { payload: serialized, key: crypto.randomUUID() }
    return attempt.key
  }

  async function submit(): Promise<Incident | null> {
    if (submitting.value) return null
    formError.value = null
    conflict.value = false
    if (!validate()) return null

    submitting.value = true
    const payload = draft()
    try {
      const current = existing.value
      return current
        ? await incidentsApi.update(current.id, current.version, payload)
        : await incidentsApi.create(payload, keyFor(payload))
    } catch (raw) {
      const error = toApiError(raw)
      fieldErrors.value = fieldMessagesFor(error)
      conflict.value = error.code === 'VERSION_CONFLICT'
      formError.value = error.isValidation || conflict.value ? null : messageFor(error)
      return null
    } finally {
      submitting.value = false
    }
  }

  return { form, status, loadError, fieldErrors, formError, conflict, submitting, suggestedDueDate, prepare, submit }
}
