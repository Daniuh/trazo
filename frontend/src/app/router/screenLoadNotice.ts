// Cuando una pantalla no se puede descargar (hay una versión nueva desplegada o se cortó la conexión), avisa con
// "Recargar", que abre la pantalla que se buscaba. Sin esto, un clic en el menú no haría nada.
// Documentado en docs/frontend/http-y-errores.md
import type { Router } from 'vue-router'
import { ApiError, ClientErrorCode, isScreenLoadError } from '@/shared/http'

type NotifyError = (error: ApiError, action: { label: string; handler: () => void }) => void

export function installScreenLoadNotice(
  router: Router,
  notifyError: NotifyError,
  reload: (path: string) => void = (path) => window.location.assign(path),
): void {
  router.onError((error, to) => {
    if (!isScreenLoadError(error)) return
    notifyError(new ApiError('network', ClientErrorCode.ScreenLoadFailed), {
      label: 'Recargar',
      handler: () => reload(to.fullPath),
    })
  })
}
