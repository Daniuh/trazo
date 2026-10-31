// Cambio de estado con la confirmación y el aviso de la interfaz: pasar a un estado final pide confirmar, porque no tiene
// vuelta atrás. Lo usan el detalle y la vista previa del listado. Documentado en docs/frontend/incidents.md
import type { Ref } from 'vue'
import { useFeedback } from '@/shared/ui/feedback/useFeedback'
import { useCatalogStore } from '../application/useCatalogStore'
import type { Incident } from '../domain/incident'

export function useStatusChange(incident: Ref<Incident | null>, change: (code: string) => Promise<boolean>) {
  const { confirm, notify } = useFeedback()
  const catalogs = useCatalogStore()

  return async function changeStatus(code: string): Promise<boolean> {
    const target = incident.value?.nextStatuses.find((status) => status.code === code)
    if (!target) return false

    if (catalogs.isFinalStatus(code)) {
      const ok = await confirm({
        title: `¿Pasar a "${target.name}"?`,
        message: 'Después de este cambio nadie podrá editar el incidente ni cambiar su estado.',
        confirmLabel: `Pasar a ${target.name}`,
        danger: code === 'cancelled',
      })
      if (!ok) return false
    }

    const changed = await change(code)
    if (changed) notify(`El incidente pasó a "${target.name}".`)
    return changed
  }
}
