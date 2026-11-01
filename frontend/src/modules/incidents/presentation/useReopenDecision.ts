// Aprobar o rechazar una solicitud de reapertura, con su confirmación o su motivo. Lo usan el detalle y la vista previa
// del listado, para que los dos pregunten y avisen igual. Documentado en docs/frontend/incidents.md
import { useFeedback } from '@/shared/ui/feedback/useFeedback'
import type { useIncidentDetail } from '../application/useIncidentDetail'
import type { useNoteDialog } from './useNoteDialog'

type Detail = Pick<ReturnType<typeof useIncidentDetail>, 'approveReopen' | 'rejectReopen'>
type NoteDialog = Pick<ReturnType<typeof useNoteDialog>, 'ask'>

export function useReopenDecision(detail: Detail, noteDialog: NoteDialog) {
  const { confirm, notify } = useFeedback()

  async function approve(): Promise<boolean> {
    const ok = await confirm({
      title: '¿Aprobar la reapertura?',
      message: 'El incidente volverá a trabajarse y recibirá un vencimiento nuevo según su prioridad.',
      confirmLabel: 'Aprobar y reabrir',
    })
    if (!ok || !(await detail.approveReopen())) return false
    notify('Aprobaste la reapertura: el incidente se reabrió.')
    return true
  }

  async function reject(): Promise<boolean> {
    const done = await noteDialog.ask({
      title: 'Rechazar la reapertura',
      message: 'El incidente seguirá como está. Quien la pidió verá tu motivo en el historial.',
      submitLabel: 'Rechazar',
      label: '¿Por qué se rechaza?',
      required: true,
      danger: true,
      run: (reason) => detail.rejectReopen(reason ?? ''),
    })
    if (done) notify('Rechazaste la solicitud de reapertura.')
    return done
  }

  return { approve, reject }
}
