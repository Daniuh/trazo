// Eventos globales que el cliente HTTP avisa al resto de la app (p. ej. para redirigir al login).
// Documentado en docs/frontend/http-y-errores.md

export type HttpEvent = 'session-expired' | 'maintenance'

type Listener = () => void

export function createHttpEvents() {
  const listeners = new Map<HttpEvent, Set<Listener>>()

  return {
    on(event: HttpEvent, listener: Listener): () => void {
      const set = listeners.get(event) ?? new Set()
      set.add(listener)
      listeners.set(event, set)
      return () => set.delete(listener)
    },
    emit(event: HttpEvent): void {
      listeners.get(event)?.forEach((listener) => listener())
    },
  }
}

export type HttpEventBus = ReturnType<typeof createHttpEvents>

export const httpEvents = createHttpEvents()
