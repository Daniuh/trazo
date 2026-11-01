// Confirmaciones y notificaciones con el estilo de Trazo. Las pantallas los usan sin conocer Quasar.
// Documentado en docs/frontend/sistema-de-diseno/componentes.md
import { useQuasar } from 'quasar'
import { messageFor } from '@/shared/errors'
import type { ApiError } from '@/shared/http'
import AppConfirmDialog from './AppConfirmDialog.vue'

export interface ConfirmOptions {
  title: string
  message: string
  confirmLabel: string
  danger?: boolean
}

export function useFeedback() {
  const $q = useQuasar()

  const confirm = (options: ConfirmOptions) =>
    new Promise<boolean>((resolve) => {
      $q.dialog({ component: AppConfirmDialog, componentProps: options })
        .onOk(() => resolve(true))
        .onCancel(() => resolve(false))
    })

  // Con una acción (p. ej. "Abrir"), el aviso se queda más tiempo para alcanzar a usarla
  const notify = (message: string, action?: { label: string; handler: () => void }) =>
    $q.notify({
      message,
      classes: 'app-notify',
      position: 'bottom',
      timeout: action ? 8000 : 4000,
      actions: action ? [{ label: action.label, color: 'white', handler: action.handler }] : undefined,
    })

  // Los errores se quedan más tiempo y se cierran a mano: el usuario debe alcanzar a leerlos
  const notifyError = (error: ApiError) =>
    $q.notify({
      message: messageFor(error),
      classes: 'app-notify app-notify--error',
      position: 'bottom',
      timeout: 0,
      actions: [{ label: 'Cerrar', color: 'white' }],
    })

  return { confirm, notify, notifyError }
}
