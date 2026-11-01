// Identificador de la conexión de tiempo real de esta pestaña. El cliente HTTP lo manda en X-Socket-ID para que la API
// no le avise a esta misma pestaña de un cambio que ella hizo. Lo registra el módulo realtime al conectarse.
// Documentado en docs/frontend/http-y-errores.md

let source: () => string | null = () => null

export function setSocketIdSource(next: () => string | null): void {
  source = next
}

export const currentSocketId = (): string | null => source()
