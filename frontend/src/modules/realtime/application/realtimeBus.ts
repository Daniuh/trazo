// Bus de avisos de tiempo real: la conexión publica y las pantallas escuchan, sin conocer Echo ni los canales.
// useRealtime deja de escuchar sola al desmontar la pantalla. Documentado en docs/frontend/realtime.md
import { onBeforeUnmount } from 'vue'
import type { RealtimeEvents } from '../domain/realtime'

type Listener<K extends keyof RealtimeEvents> = (payload: RealtimeEvents[K]) => void

const listeners = new Map<keyof RealtimeEvents, Set<Listener<never>>>()

export function onRealtime<K extends keyof RealtimeEvents>(event: K, listener: Listener<K>): () => void {
  const set = listeners.get(event) ?? new Set()
  set.add(listener as Listener<never>)
  listeners.set(event, set)
  return () => set.delete(listener as Listener<never>)
}

export function emitRealtime<K extends keyof RealtimeEvents>(event: K, payload: RealtimeEvents[K]): void {
  listeners.get(event)?.forEach((listener) => (listener as Listener<K>)(payload))
}

export function useRealtime<K extends keyof RealtimeEvents>(event: K, listener: Listener<K>): void {
  onBeforeUnmount(onRealtime(event, listener))
}
