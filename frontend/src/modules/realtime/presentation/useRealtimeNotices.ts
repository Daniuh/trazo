// Lo que llega por el canal personal. "Te asignaron": pide el incidente a la API (el aviso no trae datos) y lo anuncia
// con "Abrir". "Tu sesión cambió": vuelve a pedir la sesión; si ya no vale, la API responde que no y la app la cierra
// con su aviso, y si cambiaron los permisos, el menú y los botones se ajustan solos. Documentado en docs/frontend/realtime.md
import { useRouter } from 'vue-router'
import { useSessionStore } from '@/modules/auth/application/useSessionStore'
import { incidentsApi } from '@/modules/incidents/infrastructure/incidentsApi'
import { useFeedback } from '@/shared/ui/feedback/useFeedback'
import { useRealtime } from '../application/realtimeBus'

export function useRealtimeNotices() {
  const router = useRouter()
  const session = useSessionStore()
  const { notify } = useFeedback()

  useRealtime('assigned', async ({ id }) => {
    try {
      const incident = await incidentsApi.get(id)
      notify(`Te asignaron ${incident.code}: ${incident.title}`, {
        label: 'Abrir',
        handler: () => void router.push({ name: 'incident-detail', params: { id } }),
      })
    } catch {
      // Si ya no se puede ver (lo reasignaron enseguida, por ejemplo), no hay nada que anunciar
    }
  })

  useRealtime('session', () => {
    // Una sesión cerrada responde UNAUTHENTICATED y el cliente HTTP lleva al login
    session.refresh().catch(() => undefined)
  })
}
