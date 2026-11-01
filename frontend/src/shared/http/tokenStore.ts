// Guarda el token de sesión en memoria y en sessionStorage (nunca en localStorage).
// sessionStorage se borra al cerrar la pestaña y no se comparte entre pestañas.
// Documentado en docs/frontend/http-y-errores.md

const STORAGE_KEY = 'trazo.session'

export interface TokenStore {
  get(): string | null
  set(token: string): void
  clear(): void
}

// El almacenamiento puede estar bloqueado (modo privado, políticas del navegador): la memoria sigue funcionando
function safeStorage(): Storage | null {
  try {
    return window.sessionStorage
  } catch {
    return null
  }
}

export function createTokenStore(storage: Storage | null = safeStorage()): TokenStore {
  let token: string | null = storage?.getItem(STORAGE_KEY) ?? null

  return {
    get: () => token,
    set(value) {
      token = value
      storage?.setItem(STORAGE_KEY, value)
    },
    clear() {
      token = null
      storage?.removeItem(STORAGE_KEY)
    },
  }
}

export const tokenStore = createTokenStore()
