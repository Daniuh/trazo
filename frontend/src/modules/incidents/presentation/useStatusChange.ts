// Cambio de estado con el diálogo de nota y el aviso de la interfaz. La API dice qué estados exigen la nota (la solución
// al resolver, el motivo al cancelar); en los demás es opcional. Pasar a un estado final avisa que solo se podrá pedir
// reabrirlo. Lo usan el detalle y la vista previa del listado. Documentado en docs/frontend/incidents.md
import type { Ref } from 'vue'
import { useFeedback } from '@/shared/ui/feedback/useFeedback'
import { useCatalogStore } from '../application/useCatalogStore'
import type { Incident } from '../domain/incident'
import type { NoteRequest } from './useNoteDialog'

// Qué se le pregunta a la persona según el estado al que va
const NOTE_LABELS: Record<string, string> = {
  resolved: '¿Cómo se resolvió?',
  cancelled: '¿Por qué se cancela?',
}

export function useStatusChange(
  incident: Ref<Incident | null>,
  change: (code: string, note: string | null) => Promise<boolean>,
  ask: (request: NoteRequest) => Promise<boolean>,
) {
  const { notify } = useFeedback()
  const catalogs = useCatalogStore()

  return async function changeStatus(code: string): Promise<boolean> {
    const target = incident.value?.nextStatuses.find((status) => status.code === code)
    if (!target) return false

    const changed = await ask({
      title: `¿Pasar a "${target.name}"?`,
      message: catalogs.isFinalStatus(code)
        ? 'Después de este cambio nadie podrá editarlo ni cambiar su estado; solo se podrá pedir que se reabra.'
        : undefined,
      submitLabel: `Pasar a ${target.name}`,
      label: NOTE_LABELS[code] ?? 'Nota',
      required: target.requiresNote,
      danger: code === 'cancelled',
      run: (note) => change(code, note),
    })
    if (changed) notify(`El incidente pasó a "${target.name}".`)
    return changed
  }
}
