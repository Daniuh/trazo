// Conexión con Reverb por medio de Laravel Echo. Se conecta al mismo dominio de la app (el gateway la pasa a Reverb en
// /ws). Las firmas de los canales se piden juntas, en una sola petición, con el cliente HTTP de la app (que lleva el
// token de la sesión); la configuración se guarda por pestaña. Cada petición a la API cuenta al abrir la app.
// Documentado en docs/frontend/realtime.md
import Echo from 'laravel-echo'
import Pusher, { type ChannelAuthorizationCallback } from 'pusher-js'
import { http } from '@/shared/http'

type Envelope<T> = { data: T }
// La firma de un canal, tal como la espera Pusher
type ChannelAuthorizationData = NonNullable<Parameters<ChannelAuthorizationCallback>[1]>

export interface RealtimeConfig {
  key: string
  path: string
}

const CONFIG_KEY = 'trazo.realtime'

// La configuración no cambia mientras la pestaña está abierta: se pide una vez por sesión de la pestaña
export async function realtimeConfig(): Promise<RealtimeConfig> {
  try {
    const saved = sessionStorage.getItem(CONFIG_KEY)
    if (saved) return JSON.parse(saved) as RealtimeConfig
  } catch {
    // Sin sessionStorage (modo privado estricto) se pide cada vez
  }

  const { data } = await http.get<Envelope<RealtimeConfig>>('/realtime')
  try {
    sessionStorage.setItem(CONFIG_KEY, JSON.stringify(data.data))
  } catch {
    // Igual que arriba: sin dónde guardarla, no pasa nada
  }
  return data.data
}

interface PendingSignature {
  channel: string
  callback: ChannelAuthorizationCallback
}

// Pusher pide una firma por canal, todas en el mismo momento; se juntan y van en una sola petición
function createBatchedAuthorizer() {
  let pending: PendingSignature[] = []
  let socket = ''

  async function flush(): Promise<void> {
    const batch = pending
    pending = []
    try {
      const { data } = await http.post<Envelope<Record<string, ChannelAuthorizationData | null>>>(
        '/broadcasting/auth',
        { socket_id: socket, channels: batch.map((item) => item.channel) },
      )
      for (const { channel, callback } of batch) {
        const signature = data.data[channel]
        if (signature) callback(null, signature)
        else callback(new Error(`Sin permiso para ${channel}`), null)
      }
    } catch (error) {
      batch.forEach(({ callback }) => callback(error as Error, null))
    }
  }

  return (channel: { name: string }) => ({
    authorize: (socketId: string, callback: ChannelAuthorizationCallback) => {
      socket = socketId
      pending.push({ channel: channel.name, callback })
      if (pending.length === 1) setTimeout(() => void flush(), 0)
    },
  })
}

export function createEcho(config: RealtimeConfig): Echo<'reverb'> {
  const secure = window.location.protocol === 'https:'
  const port = Number(window.location.port) || (secure ? 443 : 80)

  return new Echo({
    broadcaster: 'reverb',
    Pusher,
    key: config.key,
    wsHost: window.location.hostname,
    wsPort: port,
    wssPort: port,
    wsPath: config.path,
    forceTLS: secure,
    enabledTransports: ['ws', 'wss'],
    authorizer: createBatchedAuthorizer(),
  })
}
