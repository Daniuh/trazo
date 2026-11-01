// Conexión de tiempo real mientras hay sesión: se conecta al abrir la app, se suscribe a los canales que permiten los
// permisos (y los ajusta si cambian), publica cada aviso en el bus y avisa al reconectarse. Si el tiempo real no está
// disponible, la app sigue igual: los datos siempre salen de la API. Documentado en docs/frontend/realtime.md
import type Echo from 'laravel-echo'
import { onBeforeUnmount, onMounted, watch } from 'vue'
import { useSessionStore } from '@/modules/auth/application/useSessionStore'
import { setSocketIdSource } from '@/shared/http'
import { channelsFor, type IncidentChange, type RealtimeEvents } from '../domain/realtime'
import { createEcho, realtimeConfig } from '../infrastructure/echoClient'
import { emitRealtime } from './realtimeBus'

const START_DELAY_MS = 1500

export function useRealtimeConnection() {
  const session = useSessionStore()
  let echo: Echo<'reverb'> | null = null
  const joined = new Set<string>()
  let wasConnected = false
  let stopped = false

  function subscribe(name: string): void {
    if (!echo) return
    const channel = echo.private(name)
    channel.listen('.incident.changed', (payload: { id: string; change: IncidentChange }) =>
      emitRealtime('incident', payload),
    )
    channel.listen('.incident.commented', (payload: RealtimeEvents['comment']) => emitRealtime('comment', payload))
    if (name.startsWith('users.')) {
      channel.listen('.incident.assigned', (payload: { id: string }) => emitRealtime('assigned', payload))
      channel.listen('.session.changed', () => emitRealtime('session', {}))
      channel.listen('.notifications.changed', () => emitRealtime('notifications', {}))
    }
  }

  // Canales que corresponden ahora: entra a los nuevos y sale de los que ya no aplican
  function sync(): void {
    if (!echo || !session.user) return
    const wanted = new Set(channelsFor(session.user.id, session.can))
    for (const name of [...joined]) {
      if (!wanted.has(name)) {
        echo.leave(name)
        joined.delete(name)
      }
    }
    for (const name of wanted) {
      if (!joined.has(name)) {
        subscribe(name)
        joined.add(name)
      }
    }
  }

  async function start(): Promise<void> {
    try {
      const config = await realtimeConfig()
      if (stopped) return
      echo = createEcho(config)
    } catch {
      return
    }
    setSocketIdSource(() => echo?.socketId() ?? null)
    echo.connector.onConnectionChange((status) => {
      if (status !== 'connected') return
      if (wasConnected) emitRealtime('reconnected', {})
      wasConnected = true
    })
    sync()
  }

  watch(() => session.user?.permissions.join(), sync)

  // Se conecta un momento después de abrir la app: primero carga lo que la pantalla necesita mostrar
  let delayed: ReturnType<typeof setTimeout> | undefined
  onMounted(() => {
    delayed = setTimeout(() => void start(), START_DELAY_MS)
  })
  onBeforeUnmount(() => {
    clearTimeout(delayed)
    stopped = true
    echo?.disconnect()
    echo = null
    joined.clear()
    setSocketIdSource(() => null)
  })
}
