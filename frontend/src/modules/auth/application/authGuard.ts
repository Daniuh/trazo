// Protege las rutas: sin sesión todo lleva al login, y una sesión vencida avisa y vuelve al login.
// Es solo experiencia de uso: quien protege los datos es la API. Documentado en docs/frontend/auth.md
import type { Router } from 'vue-router'
import { httpEvents } from '@/shared/http'
import { useSessionStore } from './useSessionStore'

export const LOGIN_ROUTE = 'login'
export const SESSION_EXPIRED_REASON = 'sesion-vencida'

export function installAuthGuard(router: Router): void {
  router.beforeEach(async (to) => {
    const session = useSessionStore()
    const signedIn = await session.restore()

    if (to.meta.public) {
      // Quien ya tiene sesión no necesita ver el login
      return signedIn && to.name === LOGIN_ROUTE ? { name: 'home' } : true
    }
    if (signedIn) return true

    return { name: LOGIN_ROUTE, query: to.fullPath === '/' ? {} : { volver: to.fullPath } }
  })

  httpEvents.on('session-expired', () => {
    useSessionStore().clear()
    const current = router.currentRoute.value
    if (current.meta.public) return

    void router.push({ name: LOGIN_ROUTE, query: { motivo: SESSION_EXPIRED_REASON, volver: current.fullPath } })
  })
}
