// Protege las rutas: sin sesión todo lleva al login, y una sesión vencida avisa y vuelve al login.
// Es solo experiencia de uso: quien protege los datos es la API. Documentado en docs/frontend/auth.md
import type { Router } from 'vue-router'
import { httpEvents } from '@/shared/http'
import { useSessionStore } from './useSessionStore'

export const LOGIN_ROUTE = 'login'
export const CONNECTION_ROUTE = 'connection-error'
export const SESSION_EXPIRED_REASON = 'sesion-vencida'
// Después de recuperar la contraseña: el login lo avisa
export const PASSWORD_CHANGED_REASON = 'contrasena-cambiada'

export function installAuthGuard(router: Router): void {
  router.beforeEach(async (to) => {
    const session = useSessionStore()
    const state = await session.restore()

    if (to.meta.public) {
      // Quien ya tiene sesión no necesita ver el login
      return state === 'signed-in' && to.name === LOGIN_ROUTE ? { name: 'home' } : true
    }
    if (state === 'signed-in') return true

    // Sin poder comprobar la sesión no se manda al login: la sesión puede seguir vigente
    const back = to.fullPath === '/' ? {} : { volver: to.fullPath }
    return { name: state === 'unreachable' ? CONNECTION_ROUTE : LOGIN_ROUTE, query: back }
  })

  httpEvents.on('session-expired', () => {
    useSessionStore().clear()
    const current = router.currentRoute.value
    if (current.meta.public) return

    void router.push({ name: LOGIN_ROUTE, query: { motivo: SESSION_EXPIRED_REASON, volver: current.fullPath } })
  })
}
